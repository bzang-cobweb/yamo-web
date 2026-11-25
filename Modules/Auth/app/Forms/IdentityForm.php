<?php

namespace Modules\Auth\Forms;

use Modules\Auth\Models\User;
use Modules\Auth\Services\IdentityDocumentService;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;

/**
 * « S'identifier », depuis le menu du profil (décision 2026-11-25-identification) : le compte envoie une pièce
 * d'identité (photo ou PDF) ; l'équipe la regarde au back-office et remplit sa date d'identification. La note dit où
 * en est le compte : à envoyer, en cours de vérification, identifié depuis le…
 */
class IdentityForm extends Form
{
    public static function name(): string
    {
        return 'auth.identity';
    }

    public function fields(): array
    {
        return [
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
