<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;

/**
 * Un code envoyé vers une destination (e-mail ou téléphone E.164), haché, pour une
 * opération identifiée par son défi.
 *
 * @property string $challenge
 * @property VerificationPurpose $purpose
 * @property VerificationChannel $channel
 * @property string $destination
 * @property array<string, mixed>|null $payload
 */
#[Fillable(['challenge', 'purpose', 'user_id', 'channel', 'destination', 'payload', 'code_hash', 'attempts', 'sent_at', 'expires_at', 'verified_at'])]
class VerificationCode extends Model
{
    protected $hidden = ['code_hash', 'payload'];

    protected function casts(): array
    {
        return [
            'purpose' => VerificationPurpose::class,
            'channel' => VerificationChannel::class,
            // Données en attente (inscription : mot de passe déjà haché…), chiffrées.
            'payload' => 'encrypted:array',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Le compte visé, même en attente de suppression (il peut se récupérer).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** @param Builder<self> $query */
    public function scopeOfChallenge(Builder $query, string $challenge): void
    {
        $query->where('challenge', $challenge);
    }
}
