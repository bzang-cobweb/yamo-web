<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Mail\PhoneReleasedMail;
use Modules\Auth\Models\User;
use Modules\Auth\Services\VerificationCodeService;

/**
 * À qui appartient un numéro, face aux numéros recyclés par les opérateurs
 * (décision 2026-11-20-restauration-numero-recycle) :
 * - un compte inactif depuis plus de `config('auth.phone.inactive_days')` jours
 *   ne peut plus réinitialiser son mot de passe par SMS ;
 * - son numéro revient au nouveau titulaire qui le prouve par un code SMS, et
 *   l'ancien titulaire en est prévenu par e-mail ;
 * - un compte en attente de suppression garde son numéro jusqu'à la purge.
 */
final class PhoneOwnership
{
    /**
     * Dernière activité (à défaut la création) plus vieille que le délai réglé.
     */
    public static function isInactive(User $user): bool
    {
        $lastActivity = $user->last_seen_at ?? $user->created_at;

        return $lastActivity instanceof Carbon
            && $lastActivity->lt(now()->subDays((int) config('auth.phone.inactive_days')));
    }

    /**
     * Le numéro (E.164) est-il tenu par un autre compte ? Un compte actif, ou en
     * attente de suppression, le tient ; un compte inactif le cède.
     */
    public static function isTaken(string $phone, ?int $ignoreUserId = null): bool
    {
        $holder = self::holder($phone, $ignoreUserId);

        return $holder !== null && ($holder->trashed() || ! self::isInactive($holder));
    }

    /**
     * Le numéro vient d'être prouvé par un code SMS : il est retiré du compte
     * inactif qui le portait encore.
     */
    public static function release(string $phone, ?int $ignoreUserId = null): void
    {
        $holder = self::holder($phone, $ignoreUserId);

        if ($holder !== null && ! $holder->trashed() && self::isInactive($holder)) {
            $holder->forceFill(['phone' => null, 'phone_country_id' => null, 'phone_verified_at' => null])->saveQuietly();
            self::notify($holder, $phone);
        }
    }

    /**
     * Prévient l'ancien titulaire par e-mail, s'il en a un, dans sa langue. Seulement
     * une fois l'attribution du numéro enregistrée : rien ne part si elle échoue.
     */
    private static function notify(User $holder, string $phone): void
    {
        if ($holder->email === null) {
            return;
        }

        $masked = VerificationCodeService::maskDestination(VerificationChannel::SMS, $phone);

        DB::afterCommit(fn () => Mail::to($holder)->send(new PhoneReleasedMail($masked)));
    }

    private static function holder(string $phone, ?int $ignoreUserId): ?User
    {
        return User::withTrashed()->where('phone', $phone)
            ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->first();
    }
}
