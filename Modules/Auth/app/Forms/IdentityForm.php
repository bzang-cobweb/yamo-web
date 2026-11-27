<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Modules\Auth\Enums\IdentityDocumentType;
use Modules\Auth\Models\User;
use Modules\Auth\Services\IdentityDocumentService;
use Modules\Location\Models\Country;
use Modules\Location\Support\CurrentCountry;

/**
 * « S'identifier », depuis le menu du profil (décision 2026-11-25-identification) : le compte envoie une pièce
 * d'identité (photo ou PDF) ; l'équipe la regarde au back-office et remplit sa date d'identification. La note dit où
 * en est le compte : à envoyer, en cours de vérification, identifié depuis le…
 *
 * Avant les photos : une note (ces informations ne sont pas partagées), le pays qui a délivré la pièce et son type
 * (décision 2026-11-27-identite-pays-et-type-de-piece).
 */
class IdentityForm extends Form
{
    public static function name(): string
    {
        return 'auth.identity';
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;
        // Le pays déjà choisi, sinon celui du compte, sinon le pays courant.
        $country = $user?->identity_country_id ?? $user?->country_id ?? CurrentCountry::get()?->getKey();

        return [
            Field::select(IdentityDocumentService::COUNTRY_FIELD)
                ->notice(__('auth::identity.not_shared'))
                ->label(__('auth::identity.country'))
                ->options(Country::options())
                ->searchable()
                ->default($country === null ? null : (string) $country)
                ->required(),
            Field::radio(IdentityDocumentService::TYPE_FIELD)
                ->label(__('auth::identity.document_type'))
                ->options(IdentityDocumentType::options())
                ->default($user?->identity_document_type?->value)
                ->required(),
            Field::upload(IdentityDocumentService::FIELD)
                ->label(__('auth::identity.documents'))
                ->help(__('auth::identity.documents_help', ['max' => IdentityDocumentService::MAX_FILES]))
                ->accept(IdentityDocumentService::acceptedMimeTypes())
                ->maxSize(IdentityDocumentService::MAX_KB)
                ->maxFiles(IdentityDocumentService::MAX_FILES)
                ->addLabel(__('auth::identity.add'))
                ->required()
                // Les photos déjà envoyées, pour en ajouter (décision 2026-11-25-identite-plusieurs-photos).
                ->files($this->user instanceof User ? app(IdentityDocumentService::class)->current($this->user) : []),
        ];
    }

    public function title(): string
    {
        return __('auth::identity.title');
    }

    public function titleAlign(): string
    {
        return 'center';
    }

    /** Où en est le compte : identifié depuis le…, en cours de vérification, ou à envoyer. */
    public function notice(): ?string
    {
        $user = $this->user instanceof User ? $this->user : null;
        if ($user?->identified_at !== null) {
            return __('auth::identity.notice.identified', ['date' => $user->identified_at->copy()->locale(app()->getLocale())->isoFormat('LL')]);
        }
        if ($user !== null && $user->getMedia(IdentityDocumentService::COLLECTION)->isNotEmpty()) {
            return __('auth::identity.notice.pending');
        }

        return __('auth::identity.notice.missing');
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.identity.update', SuccessAction::PROFILE, __('auth::identity.sent'));
    }

    public function submitLabel(): string
    {
        return __('auth::identity.submit');
    }
}
