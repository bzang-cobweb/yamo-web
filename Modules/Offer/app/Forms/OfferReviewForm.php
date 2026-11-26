<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;

/**
 * Son avis sur une offre (décision 2026-11-19-contrat-avis-offres), ouvert depuis
 * la fiche : `?record=` est l'identifiant de l'offre. Prérempli avec son avis s'il
 * en a un ; envoyé à `PUT /api/v1/offers/{offer}/review`, qui le crée ou le
 * remplace. Une offre qu'il ne peut pas noter répond 404, la sienne 403.
 */
class OfferReviewForm extends Form
{
    private ?Offer $offer = null;

    private ?OfferReview $review = null;

    public static function name(): string
    {
        return 'offer.review';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function forRecord(string $key): static
    {
        $offer = Offer::query()->whereKey($key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('review', $offer);

        $this->offer = $offer;
        $this->review = $offer->reviews()->where('user_id', $this->user?->getAuthIdentifier())->first();

        return $this;
    }

    public function fields(): array
    {
        return [
            Field::rating('rating')
                ->label(__('offer::review.rating'))
                ->default($this->review?->rating === null ? null : (string) $this->review->rating)
                ->required(),
            Field::textarea('comment')
                ->label(__('offer::review.comment'))
                ->placeholder(__('offer::review.comment_placeholder'))
                ->default($this->review?->comment)
                ->required()
                ->maxLength(OfferReview::COMMENT_MAX_LENGTH),
        ];
    }

    public function title(): string
    {
        return __('offer::review.'.($this->review === null ? 'create_title' : 'edit_title'));
    }

    /** Sans le titre de l'offre (décision 2026-11-26-avis-sans-titre-offre). */
    public function subtitle(): ?string
    {
        return null;
    }

    /**
     * Sans offre (`?record=` absent), le formulaire n'a nulle part où aller : aucun envoi.
     */
    public function submission(): ?Submission
    {
        return $this->offer === null ? null : Submission::toRoute(
            'api.v1.offers.review.save',
            SuccessAction::BACK,
            __('offer::review.saved'),
            parameters: ['offer' => $this->offer->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('offer::review.save');
    }
}
