<?php

namespace Bza\Form\Models;

use Bza\Form\Support\FormAuth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

/**
 * A file sent for an `upload` field, waiting for the form to be saved
 * (decision 2026-11-19-contrat-champ-upload-photos). Stored on the private disk,
 * designated by a random identifier, reserved for the account that sent it;
 * deleted (file included) once used, or after 24 h.
 *
 * @property int $id
 * @property string $uuid
 * @property int|string|null $user_id the uploading account; `null` for a visitor (decision 2026-11-27-paquet-form-acces)
 * @property string $form
 * @property string $field
 * @property string $path
 * @property string $mime_type
 * @property int $size
 */
class FormUpload extends Model
{
    use Prunable;

    public const DISK = 'local';

    /** Lifetime of an unused file, in hours. */
    public const LIFETIME_HOURS = 24;

    protected $fillable = ['uuid', 'user_id', 'form', 'field', 'path', 'mime_type', 'size'];

    /**
     * Does the identifier `$uuid` designate a pending file of account `$userId` for field `$field`? A visitor
     * (`$userId` `null`) only when visitors may upload: then holding the random identifier is the proof.
     */
    public static function isPendingFor(string $uuid, int|string|null $userId, string $field): bool
    {
        if ($userId === null && ! FormAuth::guestsMayUpload()) {
            return false;
        }

        return self::pending($userId, $field)->where('uuid', $uuid)->exists();
    }

    /**
     * The still valid files of account `$userId` (a visitor's, without account, for `null`) for field `$field`.
     *
     * @return Builder<self>
     */
    public static function pending(int|string|null $userId, string $field): Builder
    {
        return self::query()
            ->when($userId === null, fn (Builder $query) => $query->whereNull('user_id'), fn (Builder $query) => $query->where('user_id', $userId))
            ->where('field', $field)
            ->where('created_at', '>', now()->subHours(self::LIFETIME_HOURS));
    }

    /** Full path of the file on the disk. */
    public function absolutePath(): string
    {
        return Storage::disk(self::DISK)->path($this->path);
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<=', now()->subHours(self::LIFETIME_HOURS));
    }

    protected function pruning(): void
    {
        Storage::disk(self::DISK)->delete($this->path);
    }

    /** The file has been used: removed, file included. */
    public function discard(): void
    {
        $this->pruning();
        $this->delete();
    }
}
