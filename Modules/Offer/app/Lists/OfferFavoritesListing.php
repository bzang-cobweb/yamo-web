<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use App\Support\MediaUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Lists\Concerns\FiltersOffers;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferFavorite;

/**
 * « Mes favoris » (décision 2026-11-19-contrat-vedette-favoris) : les offres gardées
 * par le membre, les plus récemment ajoutées d'abord ; seules les offres publiées
 * du pays courant s'affichent (un favori d'une offre archivée reste, invisible).
 * Toucher une offre ouvre sa fiche ; son cœur plein la retire.
 */
class OfferFavoritesListing extends Listing
{
    use FiltersOffers;

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
     * La recherche, les filtres et le bouton du bas, comme l'accueil et « Mes
     * réservations » (décision 2026-11-19-favoris-recherche-filtres).
     */
    public function searchPlaceholder(): string
    {
        return __('offer::offer.browse.search');
    }

    protected function search(Builder $query, string $term): void
    {
        $query->orWhereHas('offer', fn (Builder $offer) => $offer->where(function (Builder $columns) use ($term): void {
            $this->orWhereContains($columns, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
            $columns->orWhereHas('address', fn (Builder $address) => $address->where(fn (Builder $city) => $this->orWhereContains($city, ['city'], $term)));
        }));
    }

    /**
     * Les filtres de l'accueil, parmi ses favoris.
     */
    public function filters(): array
    {
        return $this->offerFilters(Offer::query()->whereIn('id', OfferFavorite::query()->select('offer_id')->where('user_id', $this->user?->getAuthIdentifier())));
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $query->whereHas('offer', fn (Builder $offer) => $this->applyOfferFilters($offer, $filters));
    }

    public function footer(): ?array
    {
        return ['label' => __('offer::booking.profile.search_home'), 'form' => null, 'link' => '/'];
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
        // Comme la vitrine (titre saisi, ligne du type dessous, décision 2026-11-21-favoris-titre-saisi-ligne-type),
        // avec un cœur plein.
        [$title, $line] = OfferCardTitle::of($offer, app()->getLocale());

        return ListItem::make($offer->getKey(), $title, $line)
            ->image(MediaUrl::onRequestHost($offer->photoUrl()))
            ->rating($offer->reviews_avg_rating, (int) $offer->reviews_count)
            ->highlight(implode(' · ', array_filter([...$domain->itemDetails($offer), $domain->formattedPrice($offer, app()->getLocale())])) ?: null)
            ->link('/offers/'.$offer->getKey())
            ->favorite(true, $offer->favoriteEndpoint());
    }
}
