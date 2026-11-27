<?php

namespace Modules\Auth\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Mail\PhoneReleasedMail;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;

/**
 * À qui appartient un numéro, face aux numéros recyclés par les opérateurs
 * (décision 2026-11-20-restauration-numero-recycle) :
 * - un compte inactif depuis plus de `config('auth.phone.inactive_days')` jours
 *   ne peut plus réinitialiser son mot de passe par SMS ;
 * - son numéro revient au nouveau titulaire qui le prouve par un code SMS, et
 *   l'ancien titulaire en est prévenu par e-mail ;
 * - un compte en attente de suppression garde son numéro jusqu'à la purge.
 * Les numéros supplémentaires (`user_phones`, décision 2026-11-27-plusieurs-numeros) suivent la même règle quand on
 * en ajoute un : tenu ailleurs, il est refusé ; tenu par un compte inactif, il lui est retiré une fois prouvé.
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

        // Numéro supplémentaire d'un compte inactif : retiré de même, sans e-mail (ce n'est pas son numéro de connexion).
        self::extras($phone, $ignoreUserId)
            ->filter(fn (UserPhone $extra): bool => ! $extra->user->trashed() && self::isInactive($extra->user))
            ->each(fn (UserPhone $extra) => $extra->delete());
    }

    /**
     * Le numéro (E.164) est-il un numéro supplémentaire d'un autre compte, actif ou en attente de suppression ? Un compte
     * inactif le cède, comme son numéro principal.
     */
    public static function isTakenAsExtra(string $phone, ?int $ignoreUserId = null): bool
    {
        return self::extras($phone, $ignoreUserId)
            ->contains(fn (UserPhone $extra): bool => $extra->user->trashed() || ! self::isInactive($extra->user));
    }

    /**
     * @return Collection<int, UserPhone>
     */
    private static function extras(string $phone, ?int $ignoreUserId): Collection
    {
        return UserPhone::query()->where('phone', $phone)
            ->when($ignoreUserId !== null, fn ($query) => $query->where('user_id', '!=', $ignoreUserId))
            ->with(['user' => fn ($query) => $query->withTrashed()])
            ->get()
            ->filter(fn (UserPhone $extra): bool => $extra->user !== null)
            ->values();
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

        $masked = Masks::phone($phone);

        DB::afterCommit(fn () => Mail::to($holder)->send(new PhoneReleasedMail($masked)));
    }

    private static function holder(string $phone, ?int $ignoreUserId): ?User
    {
        return User::withTrashed()->where('phone', $phone)
            ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->first();
    }
}
