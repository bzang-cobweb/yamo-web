<?php

namespace Modules\Offer\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;

/**
 * Écrire à l'éditeur d'une offre, ou lui répondre (décision 2026-11-26-messages-editeur) : `?record={offre}` pour un
 * membre qui écrit à l'éditeur, `?record={offre}.{membre}` pour l'éditeur qui répond à ce membre. Un message, envoyé à
 * `POST /api/v1/offers/{offer}/messages` (avec `member` pour une réponse) ; les droits sont ceux de
 * `OfferPolicy::message()`.
 */
class OfferMessageForm extends Form
{
    /** Le nombre de caractères d'un message, au plus. */
    public const MAX_LENGTH = 1000;

    private ?Offer $offer = null;

    /** Le membre à qui répond l'éditeur ; `null` : un membre écrit à l'éditeur. */
    private ?User $member = null;

    public static function name(): string
    {
        return 'offer.message';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function forRecord(string $key): static
    {
        [$offerKey, $memberKey] = array_pad(explode('.', $key, 2), 2, null);
        abort_unless(ctype_digit((string) $offerKey) && ($memberKey === null || ctype_digit($memberKey)), 404);

        $offer = Offer::query()->whereKey((int) $offerKey)->first() ?? abort(404);
        $member = $memberKey === null ? null : (User::query()->whereKey((int) $memberKey)->first() ?? abort(404));
        Gate::forUser($this->user)->authorize('message', [$offer, $member]);

        $this->offer = $offer;
        $this->member = $member;

        return $this;
    }

    public function fields(): array
    {
        return [
            Field::textarea('message')
                ->label(__('offer::message.field'))
                // Le libellé seulement pour l'accessibilité : le titre du formulaire le dit déjà (décision
                // 2026-11-26-message-sans-libelle).
                ->hideLabel()
                ->placeholder(__('offer::message.placeholder'))
                ->required()
                ->maxLength(self::MAX_LENGTH),
            // Le membre à qui répond l'éditeur ; vide quand un membre écrit à l'éditeur.
            Field::hidden('member')
                ->default($this->member === null ? null : (string) $this->member->getKey()),
        ];
    }

    /**
     * Le prénom de qui va lire le message, en titre du panneau (décision 2026-11-27-message-titre-prenom) : l'éditeur de
     * l'offre quand un membre lui écrit, le membre quand l'éditeur lui répond ; sans prénom, « Envoyer un message ».
     */
    public function title(): string
    {
        $recipient = $this->member ?? $this->offer?->user;
        $name = $recipient === null ? '' : trim((string) $recipient->first_name);

        return $name !== '' ? $name : (string) __('offer::message.title');
    }

    /**
     * Ni le titre de l'offre (décision 2026-11-26-message-sans-titre-offre) ni, pour une réponse, le prénom du membre :
     * rien au-dessus du champ (décision 2026-11-27-message-sans-prenom).
     */
    public function subtitle(): ?string
    {
        return null;
    }

    /** Sans offre (`?record=` absent), le formulaire n'a nulle part où aller : aucun envoi. */
    public function submission(): ?Submission
    {
        return $this->offer === null ? null : Submission::toRoute(
            'api.v1.offers.messages.store',
            SuccessAction::BACK,
            __('offer::message.sent'),
            parameters: ['offer' => $this->offer->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('offer::message.submit');
    }
}
