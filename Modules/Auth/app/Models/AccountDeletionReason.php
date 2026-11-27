<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La raison donnée par un membre en supprimant son compte (décision 2026-11-27-confidentialite), pour l'équipe.
 * Gardée après la purge du compte (`user_id` vide).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $reason
 */
#[Fillable(['user_id', 'reason'])]
class AccountDeletionReason extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
