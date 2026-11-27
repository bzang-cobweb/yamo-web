<?php

namespace Modules\Offer\Services;

use App\Support\MediaUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\WishlistKindEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\Wishlist;
use Modules\Offer\Models\WishlistItem;

/**
 * Les listes de favoris d'un membre (décision 2026-11-27-listes-de-favoris), un seul domicile pour leurs règles :
 * - une offre est « en favori » quand elle est dans au moins une de ses listes nommées ;
 * - « Récemment consultés » est créée à la demande, remplie à l'ouverture d'une fiche (pas la sienne), la dernière
 *   ouverte en tête, au plus `Wishlist::RECENT_LIMIT` offres.
 */
class WishlistService
{
    /**
     * Ses listes nommées, la plus récente d'abord, avec leur nombre d'offres visibles.
     *
     * @return Collection<int, Wishlist>
     */
    public function customLists(Authenticatable $user): Collection
    {
        return Wishlist::query()
            ->ownedBy($user->getAuthIdentifier())
            ->custom()
            ->withCount(['items' => fn ($items) => $items->whereHas('offer', fn ($offer) => $offer->visibleToOthers())])
            ->orderByDesc('id')
            ->get();
    }

    /** La photo de la dernière offre visible rangée dans `$list`, ou `null`. */
    public function cover(Wishlist $list): ?string
    {
        $offer = Offer::query()
            ->visibleToOthers()
            ->with('media')
            ->whereIn('id', $list->items()->select('offer_id'))
            ->orderByDesc(WishlistItem::query()->select('updated_at')->where('wishlist_id', $list->getKey())->whereColumn('wishlist_items.offer_id', 'offers.id')->limit(1))
            ->orderByDesc('id')
            ->first();

        return $offer === null ? null : MediaUrl::onRequestHost($offer->photoUrl('list'));
    }

    /**
     * Range `$offer` dans sa liste `$wishlistId`, dans une nouvelle liste nommée `$name`, ou, sans l'un ni l'autre
     * (une app d'avant les listes), dans sa première liste nommée, créée « Favoris » s'il n'en a pas. Rend la liste.
     */
    public function add(Authenticatable $user, Offer $offer, int|string|null $wishlistId = null, ?string $name = null): Wishlist
    {
        $wishlist = match (true) {
            $wishlistId !== null => Wishlist::query()->ownedBy($user->getAuthIdentifier())->custom()->whereKey($wishlistId)->firstOrFail(),
            filled($name) => Wishlist::query()->create(['user_id' => $user->getAuthIdentifier(), 'name' => trim((string) $name), 'kind' => WishlistKindEnum::CUSTOM]),
            default => $this->defaultList($user),
        };

        $wishlist->items()->firstOrCreate(['offer_id' => $offer->getKey()]);
        $wishlist->touch();

        return $wishlist;
    }

    /** Retire `$offer` de toutes ses listes nommées (le cœur plein touché) ; « Récemment consultés » la garde. */
    public function removeEverywhere(Authenticatable $user, Offer $offer): void
    {
        WishlistItem::query()
            ->where('offer_id', $offer->getKey())
            ->whereIn('wishlist_id', Wishlist::query()->select('id')->ownedBy($user->getAuthIdentifier())->custom())
            ->delete();
    }

    /** Retire `$offer` de la liste `$wishlist`. */
    public function remove(Wishlist $wishlist, Offer $offer): void
    {
        $wishlist->items()->where('offer_id', $offer->getKey())->delete();
    }

    /** Sa première liste nommée ; « Favoris », dans sa langue, s'il n'en a pas. */
    public function defaultList(Authenticatable $user): Wishlist
    {
        $locale = $user instanceof User ? $user->preferredLocale() : null;

        return Wishlist::query()->ownedBy($user->getAuthIdentifier())->custom()->oldest('id')->first()
            ?? Wishlist::query()->create([
                'user_id' => $user->getAuthIdentifier(),
                'name' => (string) __('offer::offer.wishlists.default_name', [], $locale),
                'kind' => WishlistKindEnum::CUSTOM,
            ]);
    }

    /** Sa liste « Récemment consultés », s'il en a une. */
    public function recentList(Authenticatable $user): ?Wishlist
    {
        return Wishlist::query()->ownedBy($user->getAuthIdentifier())->where('kind', WishlistKindEnum::RECENT->value)->oldest('id')->first();
    }

    /** Sa liste « Récemment consultés », créée si elle n'existe pas encore (« Mes favoris » la montre toujours). */
    public function ensureRecentList(Authenticatable $user): Wishlist
    {
        return $this->recentList($user)
            ?? Wishlist::query()->create(['user_id' => $user->getAuthIdentifier(), 'name' => null, 'kind' => WishlistKindEnum::RECENT]);
    }

    /**
     * La fiche de `$offer` ouverte par `$user` : elle passe en tête de « Récemment consultés » (créée au besoin) ; au-delà
     * de `Wishlist::RECENT_LIMIT`, les plus anciennes sortent. Rien pour son propre logement.
     */
    public function recordView(Authenticatable $user, Offer $offer): void
    {
        if ((int) $offer->user_id === (int) $user->getAuthIdentifier()) {
            return;
        }

        DB::transaction(function () use ($user, $offer): void {
            $recent = $this->ensureRecentList($user);

            $item = $recent->items()->firstOrNew(['offer_id' => $offer->getKey()]);
            // Déjà là : seulement la date, qui la remet en tête.
            $item->exists ? $item->touch() : $item->save();

            $kept = $recent->items()->orderByDesc('updated_at')->orderByDesc('id')->limit(Wishlist::RECENT_LIMIT)->pluck('id');
            $recent->items()->whereNotIn('id', $kept)->delete();
        });
    }
}
