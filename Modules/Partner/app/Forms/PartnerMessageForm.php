<?php

namespace Modules\Partner\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Illuminate\Support\Facades\Gate;
use Modules\Partner\Models\Partner;

/**
 * Écrire aux autres membres d'un partenaire, dans son fil (décision 2026-11-27-partenaire-messages) : `?record={partenaire}`.
 * Un message, envoyé à `POST /api/v1/partners/{partner}/messages` ; les droits sont ceux de `PartnerPolicy::message()`.
 */
class PartnerMessageForm extends Form
{
    /** Le nombre de caractères d'un message, au plus. */
    public const MAX_LENGTH = 1000;

    private ?Partner $partner = null;

    public static function name(): string
    {
        return 'partner.message';
    }

    public function forRecord(string $key): static
    {
        abort_unless(ctype_digit($key), 404);
        $partner = Partner::query()->whereKey((int) $key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('message', $partner);
        $this->partner = $partner;

        return $this;
    }

    public function fields(): array
    {
        return [
            Field::textarea('message')
                ->label(__('partner::partner.message.field'))
                // Le libellé pour l'accessibilité seulement, comme le message d'une offre (décision 2026-11-26-message-sans-libelle).
                ->hideLabel()
                ->placeholder(__('partner::partner.message.placeholder'))
                ->required()
                ->maxLength(self::MAX_LENGTH),
        ];
    }

    /** Le nom du partenaire, en titre du panneau : ses membres liront le message. */
    public function title(): string
    {
        $name = $this->partner === null ? '' : trim((string) $this->partner->getTranslation('name', app()->getLocale()));

        return $name !== '' ? $name : (string) __('partner::partner.message.title');
    }

    /** Sans partenaire (`?record=` absent), aucun envoi. */
    public function submission(): ?Submission
    {
        return $this->partner === null ? null : Submission::toRoute(
            'api.v1.partners.messages.store',
            SuccessAction::BACK,
            __('partner::partner.message.sent'),
            parameters: ['partner' => $this->partner->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('partner::partner.message.submit');
    }
}
