<?php

namespace Modules\Form\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

/**
 * Un fichier envoyé pour un champ `upload`, en attente de l'enregistrement du
 * formulaire (décision 2026-11-19-contrat-champ-upload-photos). Rangé sur le disque
 * privé, désigné par un identifiant aléatoire, réservé au compte qui l'a envoyé ;
 * supprimé (fichier compris) une fois utilisé, ou après 24 h.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
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

    /** Durée de vie d'un fichier non utilisé, en heures. */
    public const LIFETIME_HOURS = 24;

    protected $fillable = ['uuid', 'user_id', 'form', 'field', 'path', 'mime_type', 'size'];

    /**
     * L'identifiant `$uuid` désigne-t-il un fichier en attente du compte `$userId` pour le champ `$field` ?
     */
    public static function isPendingFor(string $uuid, int|string|null $userId, string $field): bool
    {
        return $userId !== null && self::pending($userId, $field)->where('uuid', $uuid)->exists();
    }

    /**
     * Les fichiers encore valables du compte pour le champ `$field`.
     *
     * @return Builder<self>
     */
    public static function pending(int|string $userId, string $field): Builder
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('field', $field)
            ->where('created_at', '>', now()->subHours(self::LIFETIME_HOURS));
    }

    /** Chemin complet du fichier sur le disque. */
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

    /** Le fichier utilisé : retiré, fichier compris. */
    public function discard(): void
    {
        $this->pruning();
        $this->delete();
    }
}
