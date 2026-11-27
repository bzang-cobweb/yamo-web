<?php

namespace Bza\MediaManager;

use Bza\MediaManager\Models\MediaFolder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The folders and files of the manager. Rights are not checked here: the
 * caller asks MediaAccess first. Every refusal is a MediaManagerException
 * with a translated message.
 *
 * Names are unique within a folder, case-insensitively. A file is renamed or
 * moved without touching its address: only its name, or the folder it
 * belongs to, changes.
 */
class MediaLibrary
{
    public function __construct(private readonly MediaAccess $access) {}

    /**
     * @return Collection<int, MediaFolder>
     */
    public function folders(?MediaFolder $parent): Collection
    {
        return MediaFolder::query()->where('parent_id', $parent?->getKey())->orderBy('name')->get();
    }

    /**
     * @return Builder<Media>
     */
    public function files(MediaFolder $folder): Builder
    {
        return $this->managedMedia()->where('model_id', $folder->getKey());
    }

    /**
     * The media of the manager, every folder included.
     *
     * @return Builder<Media>
     */
    public function managedMedia(): Builder
    {
        $model = config('media-library.media_model', Media::class);

        return $model::query()
            ->where('model_type', (new MediaFolder)->getMorphClass())
            ->where('collection_name', MediaFolder::collectionName());
    }

    public function isManaged(Media $media): bool
    {
        return $media->model_type === (new MediaFolder)->getMorphClass()
            && $media->collection_name === MediaFolder::collectionName();
    }

    public function folderOf(Media $media): ?MediaFolder
    {
        return $this->isManaged($media) ? MediaFolder::query()->find($media->model_id) : null;
    }

    public function createFolder(?MediaFolder $parent, string $name): MediaFolder
    {
        $name = $this->validName($name);
        $this->ensureFolderNameIsFree($parent?->getKey(), $name);

        return $this->changed(MediaFolder::query()->create(['parent_id' => $parent?->getKey(), 'name' => $name]));
    }

    public function renameFolder(MediaFolder $folder, string $name): MediaFolder
    {
        $name = $this->validName($name);
        $this->ensureFolderNameIsFree($folder->parent_id, $name, except: (int) $folder->getKey());

        $folder->update(['name' => $name]);

        return $this->changed($folder);
    }

    public function moveFolder(MediaFolder $folder, ?MediaFolder $to): MediaFolder
    {
        foreach ($to?->ancestry() ?? [] as $ancestor) {
            if ($ancestor->is($folder)) {
                throw MediaManagerException::because('move_into_itself');
            }
        }

        $this->ensureFolderNameIsFree($to?->getKey(), $folder->name, except: (int) $folder->getKey());

        $folder->update(['parent_id' => $to?->getKey()]);

        return $this->changed($folder);
    }

    /**
     * Deletes an empty folder.
     */
    public function deleteFolder(MediaFolder $folder): void
    {
        if ($folder->children()->exists() || $this->files($folder)->exists()) {
            throw MediaManagerException::because('folder_not_empty');
        }

        $folder->delete();
        $this->access->flush();
    }

    /**
     * Adds a file to a folder. A name already taken gets a number: "plan-1.pdf".
     */
    public function upload(MediaFolder $folder, string $path, string $originalName): Media
    {
        $maxSize = $this->maxFileSize();

        if (is_file($path) && filesize($path) > $maxSize) {
            throw MediaManagerException::because('too_large', ['size' => Number::fileSize($maxSize)]);
        }

        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));
        $base = $this->validName(pathinfo($originalName, PATHINFO_FILENAME) ?: 'file');
        $name = $base;

        for ($i = 1; ! $this->fileNameIsFree($folder, $this->fileName($name, $extension)); $i++) {
            $name = $base.'-'.$i;
        }

        $adder = $folder->addMedia($path)
            ->usingName($name)
            ->usingFileName($this->fileName($name, $extension));

        if (! is_uploaded_file($path)) {
            $adder->preservingOriginal();
        }

        return $adder->toMediaCollection(MediaFolder::collectionName());
    }

    /**
     * Renames a file, its extension kept.
     */
    public function renameFile(Media $media, string $name): Media
    {
        $this->ensureManaged($media);

        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $name = $this->validName($extension !== '' && Str::endsWith(Str::lower($name), '.'.Str::lower($extension)) ? Str::beforeLast($name, '.') : $name);
        $fileName = $this->fileName($name, $extension);

        if (! $this->fileNameIsFree($this->folderOf($media), $fileName, except: (int) $media->getKey())) {
            throw MediaManagerException::because('name_taken', ['name' => $fileName]);
        }

        // The media library's observer renames the file on the disk.
        $media->name = $name;
        $media->file_name = $fileName;
        $media->save();

        return $media;
    }

    public function moveFile(Media $media, MediaFolder $to): Media
    {
        $this->ensureManaged($media);

        if (! $this->fileNameIsFree($to, $media->file_name, except: (int) $media->getKey())) {
            throw MediaManagerException::because('name_taken', ['name' => $media->file_name]);
        }

        // The path does not depend on the folder: the file stays where it is on the disk.
        $media->model_id = $to->getKey();
        $media->save();

        return $media;
    }

    public function deleteFile(Media $media): void
    {
        $this->ensureManaged($media);

        $media->delete();
    }

    public function maxFileSize(): int
    {
        return (int) (config('media-manager.max_file_size') ?? config('media-library.max_file_size', 10 * 1024 * 1024));
    }

    private function validName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw MediaManagerException::because('name_required');
        }

        if (in_array($name, ['.', '..'], true) || Str::contains($name, ['/', '\\', "\0"]) || mb_strlen($name) > 200) {
            throw MediaManagerException::because('name_invalid');
        }

        return $name;
    }

    private function fileName(string $name, string $extension): string
    {
        // Same sanitizing as the media library's FileAdder.
        $base = str_replace(['#', '/', '\\', ' '], '-', $name);

        return $extension === '' ? $base : $base.'.'.$extension;
    }

    private function ensureFolderNameIsFree(?int $parentId, string $name, ?int $except = null): void
    {
        $taken = MediaFolder::query()
            ->where('parent_id', $parentId)
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except))
            ->where(DB::raw('lower(name)'), mb_strtolower($name))
            ->exists();

        if ($taken) {
            throw MediaManagerException::because('name_taken', ['name' => $name]);
        }
    }

    private function fileNameIsFree(?MediaFolder $folder, string $fileName, ?int $except = null): bool
    {
        if ($folder === null) {
            return true;
        }

        return ! $this->files($folder)
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except))
            ->where(DB::raw('lower(file_name)'), mb_strtolower($fileName))
            ->exists();
    }

    private function ensureManaged(Media $media): void
    {
        if (! $this->isManaged($media)) {
            throw MediaManagerException::because('read_only');
        }
    }

    private function changed(MediaFolder $folder): MediaFolder
    {
        $this->access->flush();

        return $folder;
    }
}
