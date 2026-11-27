<?php

namespace Bza\MediaManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A folder of the media manager. Its files are media of its "files"
 * collection; a null parent means the folder sits at the root.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 */
class MediaFolder extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'media_folders';

    protected $fillable = ['parent_id', 'name'];

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MediaFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<FolderGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(FolderGrant::class, 'folder_id');
    }

    public static function collectionName(): string
    {
        return (string) config('media-manager.collection', 'files');
    }

    public function registerMediaCollections(): void
    {
        $collection = $this->addMediaCollection(self::collectionName());

        if (($disk = config('media-manager.disk')) !== null) {
            $collection->useDisk($disk);
        }
    }

    /**
     * The folders from the root down to this one, this one included.
     *
     * @return list<MediaFolder>
     */
    public function ancestry(): array
    {
        $chain = [$this];
        $folder = $this;

        while ($folder->parent_id !== null && ($folder = $folder->parent) !== null) {
            array_unshift($chain, $folder);
        }

        return $chain;
    }
}
