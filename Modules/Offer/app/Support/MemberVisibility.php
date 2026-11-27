<?php

namespace Modules\Offer\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PrivacySettings;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\OfferMessageReceived;

/**
 * Qui voit la page publique d'un membre, selon sa « Visibilité du profil » (décision 2026-11-27-confidentialite) :
 * - tout le monde : tout compte connecté ;
 * - propriétaires : le visiteur a au moins une offre (quel que soit son état) ;
 * - contacts : le visiteur et le membre ont une demande en commun (l'un a demandé une offre de l'autre) ou un échange
 *   de messages sur une offre de l'un d'eux ;
 * - propriétaires et contacts : l'un ou l'autre.
 * Le membre voit toujours sa propre page.
 */
final class MemberVisibility
{
    public static function allows(User $viewer, User $member): bool
    {
        if ($viewer->is($member)) {
            return true;
        }

        return match ($member->privacySettings()[PrivacySettings::VISIBILITY]) {
            PrivacySettings::OWNERS => self::isOwner($viewer),
            PrivacySettings::CONNECTIONS => self::areConnected($viewer, $member),
            PrivacySettings::OWNERS_AND_CONNECTIONS => self::isOwner($viewer) || self::areConnected($viewer, $member),
            default => true,
        };
    }

    /** Le compte a au moins une offre. */
    public static function isOwner(User $user): bool
    {
        return Offer::query()->where('user_id', $user->getKey())->exists();
    }

    /** Une demande ou un échange de messages entre les deux comptes, dans un sens ou dans l'autre. */
    public static function areConnected(User $one, User $other): bool
    {
        $booked = fn (User $guest, User $host): bool => Booking::query()
            ->where('user_id', $guest->getKey())
            ->whereHas('offer', fn (Builder $offer) => $offer->where('user_id', $host->getKey()))
            ->exists();

        if ($booked($one, $other) || $booked($other, $one)) {
            return true;
        }

        // Les fils de messages : `<préfixe><offre>:<membre>` (décision 2026-11-26-messages-editeur).
        $threads = fn (User $member, User $publisher): array => Offer::query()->where('user_id', $publisher->getKey())->pluck('id')
            ->map(fn (mixed $offerId): string => OfferMessageReceived::thread((int) $offerId, (int) $member->getKey()))
            ->all();
        $all = [...$threads($one, $other), ...$threads($other, $one)];

        return $all !== [] && DatabaseNotification::query()->whereIn('thread', $all)->exists();
    }
}
