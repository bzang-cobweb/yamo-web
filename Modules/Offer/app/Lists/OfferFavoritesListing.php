<?php

namespace Modules\Offer\Lists;

use App\Support\MediaUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\OfferFavorite;

/**
 * « Mes favoris » (décision 2026-11-19-contrat-vedette-favoris) : les offres gardées
 * par le membre, les plus récemment ajoutées d'abord ; seules les offres publiées
 * du pays courant s'affichent (un favori d'une offre archivée reste, invisible).
 * Toucher une offre ouvre sa fiche ; son cœur plein la retire.
 */
class OfferFavoritesListing extends Listing
{
    public static function name(): string
    {
        return 'offer.favorites';
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

    /**
     * Ses favoris (paginés par favori), sur des offres visibles.
     *
     * @return Builder<OfferFavorite>
     */
    public function query(): Builder
    {
        return OfferFavorite::query()
            ->where('user_id', $this->user?->getAuthIdentifier())
            // Visibles des autres (décision 2026-11-19-contrat-paiement-visibilite-avis).
            ->whereHas('offer', fn (Builder $offer) => $offer->visibleToOthers())
            ->with(['offer' => fn ($offer) => $offer->with(['address', 'media'])->withReviewSummary()])
            ->orderByDesc('id');
    }

    public function item(Model $record): ListItem
    {
        /** @var OfferFavorite $record */
        $offer = $record->offer;
        $domain = app(DomainRegistry::class)->require();
        $typed = (string) $offer->getTranslation('title', app()->getLocale());
        $title = $domain->itemTitle($offer) ?? $typed;

        // Comme la vitrine, avec un cœur plein.
        return ListItem::make($offer->getKey(), $title, $typed === $title ? null : $typed)
            ->image(MediaUrl::onRequestHost($offer->photoUrl()))
            ->rating($offer->reviews_avg_rating, (int) $offer->reviews_count)
            ->highlight(implode(' · ', array_filter([...$domain->itemDetails($offer), $domain->formattedPrice($offer, app()->getLocale())])) ?: null)
            ->link('/offers/'.$offer->getKey())
            ->favorite(true, $offer->favoriteEndpoint());
    }
}
