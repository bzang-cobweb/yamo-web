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
    use FiltersOffers;

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
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
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
            ->visibleToOthers()
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
            // Visibles des autres (décision 2026-11-19-contrat-paiement-visibilite-avis).
            ->visibleToOthers();
    }

    /**
     * Ville, loyer (intervalle), puis les filtres du domaine (décision
     * 2026-11-19-contrat-filtres-vitrine), parmi les offres que les visiteurs voient.
     */
    public function filters(): array
    {
        return $this->offerFilters(Offer::query()->visibleToOthers());
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $this->applyOfferFilters($query, $filters);
    }

    protected function search(Builder $query, string $term): void
    {
        $this->orWhereContains($query, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
        // La ville, affichée dans le titre de la liste.
        $query->orWhereHas('address', fn (Builder $address) => $address->where(fn (Builder $columns) => $this->orWhereContains($columns, ['city'], $term)));
    }

    /**
     * L'accueil de l'app : par ville, `$perCity` offres visibles tirées au hasard, en cartes comme la liste
     * (décision 2026-11-23-accueil-carrousels-par-ville). Les villes par ordre alphabétique.
     *
     * @return list<array{city: string, items: list<array<string, mixed>>}>
     */
    public function cityCarousels(int $perCity = 10): array
    {
        $carousels = [];
        foreach ($this->offerCities(Offer::query()->visibleToOthers()) as $city) {
            $items = $this->base()
                ->whereHas('address', fn (Builder $address) => $address->where('city', $city))
                ->inRandomOrder()
                ->limit($perCity)
                ->get()
                ->map(fn (Offer $offer): array => $this->item($offer)->toArray())
                ->values()
                ->all();
            if ($items !== []) {
                $carousels[] = ['city' => (string) $city, 'items' => $items];
            }
        }

        return $carousels;
    }

    public function item(Model $record): ListItem
    {
        /** @var Offer $record */
        $domain = app(DomainRegistry::class)->require();
        // « 60 000 FCFA / jour » : le prix et son unité, selon le domaine.
        $price = $domain->formattedPrice($record, app()->getLocale());

        // Le titre saisi ; dessous, la ligne du type (« Location meublée · Studio · Douala ») ; en gras,
        // les détails puis le prix (« 80 000 FCFA / mois », décisions 2026-11-21-cartes-titre-saisi-ligne-type
        // et 2026-11-19-ombre-coeur-onglets-offre).
        [$title, $line] = OfferCardTitle::composedFirst($record, app()->getLocale());
        $item = ListItem::make($record->getKey(), $title, $line)
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
