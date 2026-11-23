<?php

namespace Modules\Partner\Forms;

use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerLogoService;

/**
 * Proposer ou modifier un partenaire depuis l'app (décision 2026-11-24-partenaires-par-les-membres) : son nom, son
 * logo, sa description, son lien et le libellé de son bouton, dans la langue du compte ; son contact. Le pays est
 * le pays courant. Envoyé, il attend la validation de l'équipe avant d'être publié ; modifié, aussi.
 */
class PartnerForm extends Form
{
    /** Le partenaire modifié ; `null` : un nouveau. */
    private ?Partner $partner = null;

    public static function name(): string
    {
        return 'partner.partner';
    }

    /**
     * Un partenaire qu'administre le compte, dans le pays courant ; sinon 404.
     */
    public function forRecord(string $key): static
    {
        $this->partner = Partner::query()->administeredBy($this->user?->getAuthIdentifier())->whereKey($key)->first() ?? abort(404);

        return $this;
    }

    public function fields(): array
    {
        $partner = $this->partner;
        $text = fn (string $key): ?string => $partner === null ? null : (((string) $partner->getTranslation($key, $this->locale(), false)) ?: null);

        return [
            Field::text('name')
                ->label(__('partner::partner.fields.name'))
                ->required()
                ->maxLength(255)
                ->default($text('name')),
            Field::upload(PartnerLogoService::FIELD)
                ->label(__('partner::partner.fields.logo'))
                ->accept(PartnerLogoService::ACCEPTED_MIME_TYPES)
                ->maxSize(PartnerLogoService::MAX_UPLOAD_KB)
                ->maxFiles(1)
                ->files(app(PartnerLogoService::class)->current($partner)),
            Field::textarea('description')
                ->label(__('partner::partner.fields.description'))
                ->maxLength(500)
                ->default($text('description')),
            Field::text('url')
                ->label(__('partner::partner.fields.url'))
                ->placeholder('https://')
                ->maxLength(2048)
                ->rules(['url:http,https'])
                ->default($text('url')),
            Field::text('cta_label')
                ->label(__('partner::partner.fields.cta_label'))
                ->help(__('partner::partner.member.cta_help'))
                ->maxLength(60)
                ->default($text('cta_label')),
            Field::email('contact_email')
                ->label(__('partner::partner.fields.contact_email'))
                ->maxLength(255)
                ->default($partner?->contact_email ?? $this->user?->email),
            Field::text('contact_phone')
                ->label(__('partner::partner.fields.contact_phone'))
                ->maxLength(30)
                ->default($partner?->contact_phone),
        ];
    }

    public function title(): string
    {
        return $this->partner === null ? __('partner::partner.member.add') : __('partner::partner.member.edit');
    }

    /** L'équipe valide avant toute publication. */
    public function notice(): ?string
    {
        return __('partner::partner.member.review_notice');
    }

    public function submitLabel(): string
    {
        return $this->partner === null ? __('partner::partner.member.submit') : __('partner::partner.member.save');
    }

    public function submission(): ?Submission
    {
        return $this->partner === null
            ? Submission::toRoute('api.v1.partners.store', SuccessAction::BACK, __('partner::partner.member.submitted'))
            : Submission::toRoute('api.v1.partners.update', SuccessAction::BACK, __('partner::partner.member.saved'), parameters: ['partner' => $this->partner->getKey()]);
    }

    /** La langue de saisie : celle du compte. */
    public function locale(): string
    {
        return $this->user !== null && method_exists($this->user, 'preferredLocale') ? (string) $this->user->preferredLocale() : app()->getLocale();
    }
}
