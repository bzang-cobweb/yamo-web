<?php

namespace Bza\MediaManager;

use Bza\MediaManager\Models\MediaFolder;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The media attached to the other models of the application (photos,
 * avatars…), read-only: one folder per model type, then one per collection.
 * They are found in the media table, so no model has to be declared.
 */
class LinkedMedia
{
    public function enabled(): bool
    {
        return (bool) config('media-manager.linked_media', true);
    }

    /**
     * @return array<string, int> Model type (morph type) => number of files.
     */
    public function types(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return $this->query()
            ->toBase()
            ->selectRaw('model_type, count(*) as aggregate')
            ->groupBy('model_type')
            ->orderBy('model_type')
            ->pluck('aggregate', 'model_type')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<string, int> Collection name => number of files.
     */
    public function collections(string $type): array
    {
        return $this->query($type)
            ->toBase()
            ->selectRaw('collection_name, count(*) as aggregate')
            ->groupBy('collection_name')
            ->orderBy('collection_name')
            ->pluck('aggregate', 'collection_name')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * The linked media, of one model type and collection when given.
     *
     * @return Builder<Media>
     */
    public function query(?string $type = null, ?string $collection = null): Builder
    {
        $model = config('media-library.media_model', Media::class);

        return $model::query()
            ->where('model_type', '!=', (new MediaFolder)->getMorphClass())
            ->when($type !== null, fn (Builder $query) => $query->where('model_type', $type))
            ->when($collection !== null, fn (Builder $query) => $query->where('collection_name', $collection));
    }
}
