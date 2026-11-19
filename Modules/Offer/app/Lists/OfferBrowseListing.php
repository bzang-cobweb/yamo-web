<?php

namespace Modules\Offer\Lists;

use App\Support\Currency;
use App\Support\Locales;
use App\Support\MediaUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Location\Models\Address;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;

/**
 * Les offres publiées du pays courant, la vitrine de l'accueil (décision
 * 2026-11-18-accueil-offres-du-pays) : ouverte sans connexion, les plus récentes
 * d'abord ; titre construit par le domaine (« Studio · meublé · Douala »), titre
 * saisi en sous-titre, note, puis détails et prix en gras ;
 * recherche dans le titre saisi et la ville. Ni édition ni retrait.
 * Sans domaine choisi, elle n'existe pas.
 */
class OfferBrowseListing extends Listing
{
    public static function name(): string
    {
        return 'offer.browse';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    /**
     * Ouverte à tous, visiteurs compris.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }

    public function title(): string
    {
        return app(DomainRegistry::class)->require()->pluralLabel();
    }

    public function emptyText(): string
    {
        return __('offer::offer.browse.empty');
    }

    public function searchPlaceholder(): string
    {
        return __('offer::offer.browse.search');
    }

    /**
     * Publiées, dans le pays courant, les plus récentes d'abord.
     *
     * @return Builder<Offer>
     */
    public function query(): Builder
    {
        // Les vedettes épinglées en tête (`pinned()`) sont exclues ici ; au-delà de la
        // limite des épinglées, une vedette reste dans la liste, à sa place.
        return $this->base()->whereNotIn('id', $this->pinnedIds())->orderByDesc('id');
    }

    /** @var list<int>|null */
    private ?array $pinnedIds = null;

    /**
     * Les vedettes épinglées : les `pinnedLimit()` premières, la date la plus
     * lointaine d'abord, hors recherche (la même pour toutes les pages).
     *
     * @return list<int>
     */
    private function pinnedIds(): array
    {
        return $this->pinnedIds ??= Offer::query()
            ->where('country_id', CurrentCountry::require()->getKey())
            ->where('status', OfferStatusEnum::PUBLISHED)
            ->featured()
            ->orderByDesc('featured_until')->orderByDesc('id')
            ->limit($this->pinnedLimit())
            ->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    /**
     * Les offres en vedette, en tête de la première page, la date la plus lointaine
     * d'abord (décision 2026-11-19-contrat-vedette-favoris). Aucun badge.
     *
     * @return Builder<Offer>
     */
    protected function pinned(): Builder
    {
        return $this->base()->whereIn('id', $this->pinnedIds())->orderByDesc('featured_until')->orderByDesc('id');
    }

    /**
     * Publiées, du pays courant, avec ce qu'affiche un item.
     *
     * @return Builder<Offer>
     */
    private function base(): Builder
    {
        return Offer::query()
            ->with(['address', 'media'])
            // Moyenne, nombre et dernier avis (décision 2026-11-19-contrat-avis-offres).
            ->withReviewSummary()
            // Le cœur d'un membre connecté (décision 2026-11-19-contrat-vedette-favoris).
            ->when($this->user !== null, fn (Builder $query) => $query->withFavoriteOf($this->user?->getAuthIdentifier()))
            ->where('country_id', CurrentCountry::require()->getKey())
            ->where('status', OfferStatusEnum::PUBLISHED);
    }

    /**
     * Ville, loyer (intervalle), puis les filtres du domaine (décision
     * 2026-11-19-contrat-filtres-vitrine).
     */
    public function filters(): array
    {
        $domain = app(DomainRegistry::class)->require();
        $currency = (string) Currency::symbol(CurrentCountry::require()->currency);

        return array_values(array_filter([
            ! $domain->hasAddress() ? null : Field::select('city')
                ->label(__('offer::offer.filters.city'))
                ->options($this->cities()),
            // Un seul intervalle, min et max (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
            ! $domain->hasPrice() ? null : Field::range('price')
                ->label($domain->priceLabel())
                ->suffix($currency)
                ->rules(['min:0']),
            ...$domain->filters(),
        ]));
    }

    /**
     * Les villes des offres publiées du pays, par ordre alphabétique.
     *
     * @return array<string, string>
     */
    private function cities(): array
    {
        return Address::query()
            ->whereIn('id', Offer::query()
                ->select('address_id')
                ->where('country_id', CurrentCountry::require()->getKey())
                ->where('status', OfferStatusEnum::PUBLISHED))
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city', 'city')
            ->all();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['city'])) {
            $query->whereHas('address', fn (Builder $address) => $address->where('city', $filters['city']));
        }
        [$min, $max] = array_values((array) ($filters['price'] ?? [])) + [null, null];
        if (is_numeric($min)) {
            $query->where('price', '>=', $min);
        }
        if (is_numeric($max)) {
            $query->where('price', '<=', $max);
        }

        app(DomainRegistry::class)->require()->applyFilters($query, $filters);
    }

    protected function search(Builder $query, string $term): void
    {
        $this->orWhereContains($query, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
        // La ville, affichée dans le titre de la liste.
        $query->orWhereHas('address', fn (Builder $address) => $address->where(fn (Builder $columns) => $this->orWhereContains($columns, ['city'], $term)));
    }

    public function item(Model $record): ListItem
    {
        /** @var Offer $record */
        $domain = app(DomainRegistry::class)->require();
        // « 60 000 FCFA / jour » : le prix et son unité, selon le domaine.
        $price = $domain->formattedPrice($record, app()->getLocale());

        $typed = (string) $record->getTranslation('title', app()->getLocale());
        $title = $domain->itemTitle($record) ?? $typed;

        // Titre construit par le domaine (« Studio · meublé · Douala »), sinon le titre saisi ;
        // en sous-titre, le titre saisi ; en gras, les détails puis le prix
        // (« 80 000 FCFA / mois », décisions 2026-11-19-item-titre-saisi-details et 2026-11-19-ombre-coeur-onglets-offre).
        $item = ListItem::make($record->getKey(), $title, $typed === $title ? null : $typed)
            ->image(MediaUrl::onRequestHost($record->photoUrl()))
            ->rating($record->reviews_avg_rating, (int) $record->reviews_count)
            ->highlight(implode(' · ', array_filter([...$domain->itemDetails($record), $price])) ?: null)
            ->link('/offers/'.$record->getKey());

        // Un cœur pour un membre connecté, jamais sur ses propres offres.
        if ($this->user !== null && (int) $record->user_id !== (int) $this->user->getAuthIdentifier()) {
            $item->favorite((bool) $record->is_favorite, $record->favoriteEndpoint());
        }

        return $item;
    }
}
