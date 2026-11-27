<?php

namespace Bza\MediaManager\Models;

use Bza\MediaManager\Ability;
use Bza\MediaManager\Grantee;
use Bza\MediaManager\Target;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The rights of one grantee (role, user) on one target: each ability is
 * given (true) or refused (false); an ability left out is inherited.
 *
 * @property int|null $folder_id
 * @property string|null $linked_type
 * @property string $grantee_type
 * @property string $grantee_id
 * @property array<string, bool> $abilities
 */
class FolderGrant extends Model
{
    protected $table = 'media_folder_grants';

    protected $fillable = ['folder_id', 'linked_type', 'grantee_type', 'grantee_id', 'abilities'];

    protected function casts(): array
    {
        return ['abilities' => 'array'];
    }

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function grantee(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<FolderGrant>  $query
     */
    public function scopeOnTarget(Builder $query, Target $target): void
    {
        $query->where('folder_id', $target->folderId)->where('linked_type', $target->linkedType);
    }

    public function target(): Target
    {
        return match (true) {
            $this->folder_id !== null => Target::folder($this->folder_id),
            $this->linked_type !== null => Target::linked($this->linked_type),
            default => Target::root(),
        };
    }

    public function granteeKey(): string
    {
        return (new Grantee($this->grantee_type, (string) $this->grantee_id))->key();
    }

    /**
     * True when given here, false when refused here, null when inherited.
     */
    public function value(Ability $ability): ?bool
    {
        $value = $this->abilities[$ability->value] ?? null;

        return $value === null ? null : (bool) $value;
    }
}
