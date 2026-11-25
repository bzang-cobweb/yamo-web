<?php

namespace Modules\Offer\Lists;

use App\Models\Setting;
use App\Support\Locales;
use App\Support\MediaUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Lists\Concerns\FiltersOffers;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferCounterService;

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
        // Les vedettes, toutes épinglées en tête (`pinned()`), sont exclues ici (décision 2026-11-25-vedettes-d-abord).
        return $this->base()->whereNotIn('id', $this->pinnedIds())->orderByDesc('id');
    }

    /**
     * Toutes les vedettes en tête, sans limite : elles sont le premier critère d'ordre de la recherche (décision
     * 2026-11-25-vedettes-d-abord).
     */
    public function pinnedLimit(): int
    {
        return PHP_INT_MAX;
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
        // La région obligatoire (décision 2026-11-25-region-obligatoire-recherche).
        return $this->offerFilters(Offer::query()->visibleToOthers(), regionRequired: true);
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

    /** La conversion des photos : celle des listes, celle de l'accueil pour ses carrousels. */
    private string $photoConversion = 'list';

    /**
     * L'accueil de l'app : par région (décision 2026-11-25-region-de-l-adresse, à la place des villes de la décision
     * 2026-11-23-accueil-carrousels-par-ville), `$perCity` offres visibles tirées au hasard, en cartes comme la liste.
     * Les régions dans l'ordre du back-office ; chacune avec le filtre de la recherche qu'ouvre « Voir tout ».
     *
     * @return list<array{title: string, filter: array{region: string}, items: list<array<string, mixed>>}>
     */
    public function regionCarousels(?int $perCity = null): array
    {
        // Les cartes de l'accueil, deux par ligne : leurs photos à cette taille (décision
        // 2026-11-24-avatar-photos-offre-conversions).
        $this->photoConversion = 'home';
        // Le nombre réglé dans « Application » (décision 2026-11-23-accueil-nombres-reglables).
        $perCity ??= Setting::homeOffersPerCity();
        $carousels = [];
        foreach (OfferRegions::of(Offer::query()->visibleToOthers()) as $region) {
            $items = $this->base()
                ->whereHas('address', fn (Builder $address) => $address->where('region_id', $region->getKey()))
                ->inRandomOrder()
                ->limit($perCity)
                ->get();
            // Chacune compte une vue (décision 2026-11-25-compteurs-offres).
            app(OfferCounterService::class)->countViews($items, $this->user);
            $items = $items
                ->map(fn (Offer $offer): array => $this->item($offer)->toArray())
                ->values()
                ->all();
            if ($items !== []) {
                $carousels[] = ['title' => OfferRegions::name($region), 'filter' => ['region' => (string) $region->getKey()], 'items' => $items];
            }
        }

        return $carousels;
    }

    /**
     * Le bloc « À la une » de l'accueil (décision 2026-11-25-bloc-a-la-une), `Setting::homeFeatured()` offres en
     * cartes comme celles des villes. Les offres en vedette d'abord, tirées au hasard. Paiement appliqué, la vedette s'achète : elles
     * seules, et le bloc est vide sans vedette. Sinon, les places restantes vont aux offres les mieux notées, les plus
     * récentes à note égale.
     *
     * @return list<array<string, mixed>>
     */
    public function featuredOffers(?int $count = null): array
    {
        // Des cartes de la taille de celles des villes, deux par ligne : la conversion de l'accueil.
        $this->photoConversion = 'home';
        $count ??= Setting::homeFeatured();
        $offers = $this->base()->featured()->inRandomOrder()->limit($count)->get();

        $missing = $count - $offers->count();
        if ($missing > 0 && ! Setting::paymentRequired()) {
            $offers = $offers->concat($this->base()
                ->whereKeyNot($offers->modelKeys())
                // Sans avis : après les offres notées.
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit($missing)
                ->get());
        }

        // Chacune compte une vue (décision 2026-11-25-compteurs-offres).
        app(OfferCounterService::class)->countViews($offers, $this->user);

        return $offers->map(fn (Offer $offer): array => $this->item($offer)->toArray())->values()->all();
    }

    /**
     * Les offres d'une page de la recherche comptent une vue, les vedettes épinglées comprises (décision
     * 2026-11-25-compteurs-offres).
     */
    protected function served(Collection $records): void
    {
        app(OfferCounterService::class)->countViews($records, $this->user);
    }

    /**
     * Sous les filtres, le nombre d'offres trouvées, recherche et filtres compris (« 12 logements »), comme les autres
     * listes (décision 2026-11-25-recherche-nombre).
     *
     * @return array{title: string|null, subtitle: string|null}
     */
    public function headingFor(array $filters, ?string $search = null): array
    {
        $count = $this->countFor($search, $filters);

        return ['title' => trans_choice('offer::offer.list.count', $count, ['count' => $count]), 'subtitle' => null];
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
            ->image(MediaUrl::onRequestHost($record->photoUrl($this->photoConversion)))
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
