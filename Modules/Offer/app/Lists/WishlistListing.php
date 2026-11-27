<?php

namespace Modules\Offer\Lists;

use Bza\Form\Support\Listing;
use Bza\Form\Support\ListItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Wishlist;
use Modules\Offer\Services\WishlistService;

/**
 * « Mes favoris », le lien du menu du profil (décision 2026-11-27-listes-de-favoris) : ses listes de favoris,
 * « Récemment consultés » en tête (toujours là, créée au besoin), puis ses listes nommées, la plus récente d'abord.
 * Chacune avec son nom, son nombre d'offres et la photo de la dernière rangée ; la toucher ouvre ses offres
 * (`/wishlists/{id}`, la liste `offer.wishlist`).
 */
class WishlistListing extends Listing
{
    public static function name(): string
    {
        return 'offer.wishlists';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function title(): string
    {
        return __('offer::offer.favorites.title');
    }

    public function emptyText(): string
    {
        return __('offer::offer.favorites.empty');
    }

    public function footer(): ?array
    {
        return ['label' => __('offer::booking.profile.search_home'), 'form' => null, 'link' => '/search'];
    }

    /**
     * Ses listes : « Récemment consultés » d'abord (`recent` après `custom` dans l'ordre inverse), puis les plus récentes.
     *
     * @return Builder<Wishlist>
     */
    public function query(): Builder
    {
        if ($this->user !== null) {
            app(WishlistService::class)->ensureRecentList($this->user);
        }

        return Wishlist::query()
            ->ownedBy($this->user?->getAuthIdentifier())
            ->withCount(['items' => fn ($items) => $items->whereHas('offer', fn ($offer) => $offer->visibleToOthers())])
            ->orderByDesc('kind')
            ->orderByDesc('id');
    }

    public function item(Model $record): ListItem
    {
        /** @var Wishlist $record */
        $count = (int) $record->items_count;

        return ListItem::make($record->getKey(), $record->displayName(), trans_choice('offer::offer.wishlists.count', $count, ['count' => $count]))
            ->image(app(WishlistService::class)->cover($record))
            // La photo en petit à gauche, comme « Mes partenaires » ; sans photo, sa place reste.
            ->imageSide()
            ->link('/wishlists/'.$record->getKey());
    }
}
