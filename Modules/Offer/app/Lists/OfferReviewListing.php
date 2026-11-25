<?php

namespace Modules\Offer\Lists;

use App\Support\DisplayTime;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;

/**
 * Tous les avis d'une offre, en liste comme « Mes logements », avec la recherche (décision
 * 2026-11-25-liste-avis-generique) : ouverte par « Voir tous les 3 avis » de la fiche, l'offre en champ caché.
 * - En titre, « Avis » ; sous la recherche, centrés, la note moyenne « 4,7 » puis « 3 avis », recherche comprise.
 * - Une carte par avis, disposée comme sur la fiche : le prénom, les étoiles et la date, le commentaire.
 * - La recherche porte sur le commentaire et le prénom.
 * - Publique, comme la fiche : seulement les avis d'une offre que le compte (ou le visiteur) peut voir.
 */
class OfferReviewListing extends Listing
{
    /** Le champ caché de l'offre. */
    public const OFFER = 'offer';

    public static function name(): string
    {
        return 'offer.reviews';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    /** Comme la fiche et ses avis : un visiteur aussi. */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }

    public function title(): string
    {
        return __('offer::offer.section_titles.reviews');
    }

    /**
     * En tête de la liste, centrés : la note moyenne de l'offre, « 4,7 », au style du titre de la page, et dessous le
     * nombre d'avis, recherche comprise, « 3 avis » (décision 2026-11-25-liste-avis-note-en-tete). L'en-tête garde « Avis ».
     *
     * @return array{title: string|null, subtitle: string|null}
     */
    public function headingFor(array $filters, ?string $search = null): array
    {
        $average = $this->chosenOffer($filters)?->reviews_avg_rating;
        $count = $this->countFor($search, $filters);

        return [
            'title' => $average === null ? null : Number::format((float) $average, precision: 1, locale: $this->locale()),
            'subtitle' => $count === 0 ? null : trans_choice('offer::offer.reviews_count', $count, ['count' => $count], $this->locale()),
        ];
    }

    /** @return list<Field> */
    public function filters(): array
    {
        return [Field::hidden(self::OFFER)];
    }

    /** Toujours : les avis de l'offre choisie ; sans offre visible, aucun. */
    protected function constrain(Builder $query, array $filters): void
    {
        $offer = $this->chosenOffer($filters);
        // Sans offre visible, aucun avis.
        if ($offer === null) {
            $query->whereRaw('1 = 0');

            return;
        }
        $query->where('offer_id', $offer->getKey());
    }

    public function searchPlaceholder(): string
    {
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
    }

    protected function search(Builder $query, string $term): void
    {
        $this->orWhereContains($query, ['comment'], $term);
        $query->orWhereHas('user', fn (Builder $user) => $user->where(fn (Builder $columns) => $this->orWhereContains($columns, ['first_name'], $term)));
    }

    public function emptyText(): string
    {
        return __('offer::mobile.offer.noReviews');
    }

    /**
     * Du plus récent au plus ancien, comme sur la fiche.
     *
     * @return Builder<OfferReview>
     */
    public function query(): Builder
    {
        return OfferReview::query()->with('user')->orderByDesc('updated_at')->orderByDesc('id');
    }

    public function item(Model $record): ListItem
    {
        /** @var OfferReview $record */
        // La date formatée par le serveur, comme sur la fiche (décision 2026-11-25-dates-formatees-serveur).
        return ListItem::make($record->getKey(), (string) ($record->user?->first_name ?: $record->user?->name ?? ''), $record->comment)
            ->detail(DisplayTime::date($record->created_at, 'll', $this->locale()))
            ->rating($record->rating, 1)
            // Comme sur la fiche : le prénom, « ★★★★★ · 18 nov. 2026 », le commentaire.
            ->reviewLayout();
    }

    /**
     * L'offre des filtres, si le compte (ou le visiteur) peut la voir et qu'elle montre ses avis.
     *
     * @param  array<string, mixed>  $filters
     */
    private function chosenOffer(array $filters): ?Offer
    {
        if (! isset($filters[self::OFFER]) || ! is_scalar($filters[self::OFFER])) {
            return null;
        }
        $offer = Offer::query()->find((string) $filters[self::OFFER]);
        if ($offer === null || ! $offer->showsReviews()) {
            return null;
        }

        return Gate::forUser($this->user)->allows('show', $offer) ? $offer : null;
    }

    private function locale(): string
    {
        return $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
    }
}
