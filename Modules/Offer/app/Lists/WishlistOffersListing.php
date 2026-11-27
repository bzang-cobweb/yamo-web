<?php

namespace Modules\Offer\Lists;

use App\Support\MediaUrl;
use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Listing;
use Bza\Form\Support\ListItem;
use Bza\Form\Support\Locales;
use Bza\Form\Support\Submission;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Forms\WishlistForm;
use Modules\Offer\Lists\Concerns\FiltersOffers;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\Wishlist;
use Modules\Offer\Models\WishlistItem;

/**
 * Une liste de favoris ouverte (décision 2026-11-27-listes-de-favoris ; avant, « Mes favoris », décision
 * 2026-11-19-contrat-vedette-favoris) : ses offres, la liste en filtre caché (`wishlist`), ouverte depuis « Mes favoris »
 * ou le bloc « Récemment consultés » de l'accueil. Seules les offres publiées du pays courant s'affichent (une offre
 * archivée reste rangée, invisible). Toucher une offre ouvre sa fiche.
 * - Une liste nommée : son nom en titre, les offres les plus récemment rangées d'abord, leur cœur plein (le toucher
 *   retire l'offre de toutes ses listes), et le menu ⋮ de la barre du haut : « Renommer », « Supprimer ».
 * - « Récemment consultés » : la dernière ouverte d'abord, deux cartes par ligne, une croix sur chaque carte pour la
 *   retirer ; ni cœur ni menu.
 * La recherche, les filtres et le bouton du bas, comme l'accueil (décision 2026-11-19-favoris-recherche-filtres).
 */
class WishlistOffersListing extends Listing
{
    use FiltersOffers;

    /** Le champ caché de la liste. */
    public const WISHLIST = 'wishlist';

    /** La liste choisie, une fois cherchée (par valeur du filtre). */
    private ?Wishlist $chosen = null;

    private ?string $chosenFor = null;

    public static function name(): string
    {
        return 'offer.wishlist';
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

    /** Le nom de la liste ouverte. */
    public function titleFor(array $filters): string
    {
        return $this->chosenList($filters)?->displayName() ?? $this->title();
    }

    /**
     * Sous les filtres, le nombre d'offres de la liste, recherche et filtres compris (décision
     * 2026-11-22-listes-nombre-en-tete).
     *
     * @return array{title: string|null, subtitle: string|null}
     */
    public function headingFor(array $filters, ?string $search = null): array
    {
        $count = $this->countFor($search, $filters);

        return ['title' => trans_choice('offer::offer.wishlists.count', $count, ['count' => $count]), 'subtitle' => null];
    }

    public function emptyText(): string
    {
        return __('offer::offer.wishlists.empty');
    }

    public function searchPlaceholder(): string
    {
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
    }

    protected function search(Builder $query, string $term): void
    {
        $query->orWhereHas('offer', fn (Builder $offer) => $offer->where(function (Builder $columns) use ($term): void {
            $this->orWhereContains($columns, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
            $columns->orWhereHas('address', fn (Builder $address) => $address->where(fn (Builder $city) => $this->orWhereContains($city, ['city'], $term)));
        }));
    }

    /**
     * La liste en champ caché, puis les filtres de l'accueil, parmi les offres de ses listes.
     */
    public function filters(): array
    {
        $mine = WishlistItem::query()->select('offer_id')->whereIn('wishlist_id', Wishlist::query()->select('id')->ownedBy($this->user?->getAuthIdentifier()));

        return [Field::hidden(self::WISHLIST), ...$this->offerFilters(Offer::query()->whereIn('id', $mine))];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $offerFilters = array_diff_key($filters, [self::WISHLIST => true]);
        if ($offerFilters !== []) {
            $query->whereHas('offer', fn (Builder $offer) => $this->applyOfferFilters($offer, $offerFilters));
        }
    }

    /** Toujours : les offres de la liste choisie, la sienne ; sans liste à lui, aucune. */
    protected function constrain(Builder $query, array $filters): void
    {
        $list = $this->chosenList($filters);
        if ($list === null) {
            $query->whereRaw('1 = 0');

            return;
        }
        $query->where('wishlist_id', $list->getKey());
        // « Récemment consultés » : la dernière ouverte d'abord.
        if ($list->isRecent()) {
            $query->reorder()->orderByDesc('updated_at')->orderByDesc('id');
        }
    }

    /** « Récemment consultés » : deux cartes par ligne. */
    public function columnsFor(array $filters): int
    {
        return $this->chosenList($filters)?->isRecent() ? 2 : 1;
    }

    /** Le menu ⋮ d'une liste nommée : « Renommer » (son formulaire), « Supprimer » (après confirmation). */
    public function headerActionsFor(array $filters): array
    {
        $list = $this->chosenList($filters);
        if ($list === null || $list->isRecent()) {
            return [];
        }

        return [
            $this->headerAction(__('offer::offer.wishlists.rename'), form: WishlistForm::name(), record: $list->getKey()),
            $this->headerAction(
                __('offer::offer.wishlists.delete'),
                'destructive',
                __('offer::offer.wishlists.delete_confirm', ['name' => $list->displayName()]),
                Submission::toRoute('api.v1.wishlists.destroy', SuccessAction::BACK, __('offer::offer.wishlists.deleted'), true, ['wishlist' => $list->getKey()]),
            ),
        ];
    }

    public function footer(): ?array
    {
        return ['label' => __('offer::booking.profile.search_home'), 'form' => null, 'link' => '/search'];
    }

    /**
     * Les offres rangées (paginées par rangement), visibles.
     *
     * @return Builder<WishlistItem>
     */
    public function query(): Builder
    {
        return WishlistItem::query()
            // Visibles des autres (décision 2026-11-19-contrat-paiement-visibilite-avis).
            ->whereHas('offer', fn (Builder $offer) => $offer->visibleToOthers())
            ->with(['offer' => fn ($offer) => $offer->with(['address', 'media', 'country'])->withReviewSummary()])
            ->orderByDesc('id');
    }

    public function item(Model $record): ListItem
    {
        /** @var WishlistItem $record */
        $offer = $record->offer;
        $domain = app(DomainRegistry::class)->require();
        // Comme la vitrine (titre saisi, ligne du type dessous, décision 2026-11-21-favoris-titre-saisi-ligne-type).
        [$title, $line] = OfferCardTitle::composedFirst($offer, app()->getLocale());

        $item = ListItem::make($offer->getKey(), $title, $line)
            ->image(MediaUrl::onRequestHost($offer->photoUrl('list')))
            ->rating($offer->reviews_avg_rating, (int) $offer->reviews_count)
            ->highlight(implode(' · ', array_filter([...$domain->itemDetails($offer), $domain->formattedPrice($offer, app()->getLocale())])) ?: null)
            ->link('/offers/'.$offer->getKey())
            // Les étoiles ouvrent ses avis dans un panneau ; pas d'œil, la carte ouvre déjà la fiche (décision
            // 2026-11-27-favoris-etoiles-avis).
            ->ratingPanel((string) __('offer::offer.reviews'), 'offer.reviews', $offer->getKey());

        $list = $this->chosen;
        if ($list !== null && $list->isRecent()) {
            // Une croix sur la photo, qui la retire aussitôt de « Récemment consultés ».
            return $item->delete(
                __('offer::offer.wishlists.remove'),
                __('offer::offer.wishlists.remove_confirm'),
                Submission::toRoute('api.v1.wishlists.offers.destroy', SuccessAction::BACK, __('offer::offer.wishlists.removed'), true, ['wishlist' => $list->getKey(), 'offer' => $offer->getKey()]),
                onImage: true,
            );
        }

        return $item->favorite(true, $offer->favoriteEndpoint());
    }

    /**
     * La liste des filtres, si elle est au compte.
     *
     * @param  array<string, mixed>  $filters
     */
    private function chosenList(array $filters): ?Wishlist
    {
        $value = $filters[self::WISHLIST] ?? null;
        if (! is_scalar($value) || ! ctype_digit((string) $value)) {
            return null;
        }
        if ($this->chosenFor !== (string) $value) {
            $this->chosenFor = (string) $value;
            $this->chosen = Wishlist::query()->ownedBy($this->user?->getAuthIdentifier())->whereKey((string) $value)->first();
        }

        return $this->chosen;
    }
}
