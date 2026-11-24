<?php

namespace Modules\Partner\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Auth\Models\User;
use Modules\Partner\Enums\PartnerStatusEnum;

/**
 * Une demande d'un partenaire (décision 2026-11-24-partenaire-demandes) : un ajout (`created`) ou une modification
 * (`updated`) depuis l'app, en validation (`pending`) jusqu'à la réponse de l'équipe, qui lui donne le statut du
 * partenaire (publié, refusé, archivé). Une modification tant qu'elle est en validation la prolonge ; ses
 * notifications forment un fil (`partner_request:<id>:<destinataire>`).
 *
 * @property int $id
 * @property int $partner_id
 * @property int|null $user_id
 * @property string $action
 * @property PartnerStatusEnum $status
 * @property int|null $answered_by
 * @property Carbon|null $answered_at
 */
class PartnerRequest extends Model
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    /** Le préfixe du fil de ses notifications. */
    public const THREAD_PREFIX = 'partner_request:';

    protected $fillable = ['partner_id', 'user_id', 'action', 'status', 'answered_by', 'answered_at'];

    protected function casts(): array
    {
        return [
            'status' => PartnerStatusEnum::class,
            'answered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Partner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * En validation : l'équipe n'a pas encore répondu.
     *
     * @param  Builder<PartnerRequest>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', PartnerStatusEnum::PENDING->value);
    }

    /** Le fil des notifications de la demande `$requestId` chez le compte `$userId` : un par destinataire, ses étapes ne se répètent pas. */
    public static function thread(int $requestId, int|string $userId): string
    {
        return self::THREAD_PREFIX.$requestId.':'.$userId;
    }
}
