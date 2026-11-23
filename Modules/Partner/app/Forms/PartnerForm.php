<?php

namespace Modules\Partner\Forms;

use App\Support\Locales;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Step;
use Modules\Form\Support\Submission;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerLogoService;

/**
 * Proposer ou modifier un partenaire depuis l'app (décision 2026-11-24-partenaires-par-les-membres) : son nom, son
 * logo, sa description, son lien et le libellé de son bouton, dans la langue du compte (à la modification, dans la
 * langue choisie) ; son contact. Le pays est le pays courant. En étapes (décision 2026-11-24-mes-partenaires-cartes). Envoyé, il attend la validation de l'équipe avant d'être publié ; modifié, aussi.
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
        // Dans la langue de saisie ; une langue à traduire est pré-remplie avec un texte existant, comme une offre.
        $text = fn (string $key): ?string => $partner === null ? null : (((string) $partner->getTranslation($key, $this->locale())) ?: null);

        return array_values(array_filter([
            $this->contentLocaleField(),
            Field::upload(PartnerLogoService::FIELD)
                ->label(__('partner::partner.fields.logo'))
                ->accept(PartnerLogoService::ACCEPTED_MIME_TYPES)
                ->maxSize(PartnerLogoService::MAX_UPLOAD_KB)
                ->maxFiles(1)
                ->files(app(PartnerLogoService::class)->current($partner)),
            Field::text('name')
                ->label(__('partner::partner.fields.name'))
                ->required()
                ->maxLength(255)
                ->default($text('name')),
            // Obligatoire ; le logo, lui, est facultatif (décision 2026-11-24-partenaire-description-obligatoire).
            Field::textarea('description')
                ->label(__('partner::partner.fields.description'))
                ->required()
                ->maxLength(500)
                ->default($text('description')),
            Field::text('contact_phone')
                ->label(__('partner::partner.fields.contact_phone'))
                ->maxLength(30)
                ->default($partner?->contact_phone),
            Field::email('contact_email')
                ->label(__('partner::partner.fields.contact_email'))
                ->maxLength(255)
                ->default($partner?->contact_email ?? $this->user?->email),
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
        ]));
    }

    /**
     * En étapes (décision 2026-11-24-mes-partenaires-cartes) : la langue de saisie, à la modification ; le
     * partenaire (logo, nom, description) ; le contact (téléphone, e-mail, lien et son bouton).
     *
     * @return list<Step>
     */
    public function steps(): array
    {
        return array_values(array_filter([
            $this->hasContentLocale() ? Step::make(__('partner::partner.content_locale.step'), [self::CONTENT_LOCALE]) : null,
            // « Présentation » (décision 2026-11-24-etape-presentation).
            Step::make(__('partner::partner.member.steps.presentation'), [PartnerLogoService::FIELD, 'name', 'description']),
            Step::make(__('partner::partner.sections.contact'), ['contact_phone', 'contact_email', 'url', 'cta_label']),
        ]));
    }

    /** Le champ de la langue de saisie, comme pour une offre. */
    public const CONTENT_LOCALE = 'content_locale';

    /**
     * L'étape « Langue » existe-t-elle ? À la modification, quand l'app propose plusieurs langues.
     */
    private function hasContentLocale(): bool
    {
        return $this->partner !== null && count(Locales::values()) > 1;
    }

    /**
     * « Dans quelle langue modifiez-vous ce partenaire ? » : chaque langue avec son état (traduite, à traduire) ; la
     * choisir recharge le formulaire (décision 2026-11-24-mes-partenaires-cartes).
     */
    private function contentLocaleField(): ?Field
    {
        if (! $this->hasContentLocale()) {
            return null;
        }

        $translated = array_keys(array_filter($this->partner?->getTranslations('name') ?? [], fn (mixed $name): bool => filled($name)));
        $options = [];
        foreach (Locales::options() as $code => $label) {
            $options[$code] = $label.' — '.(in_array($code, $translated, true) ? __('partner::partner.content_locale.translated') : __('partner::partner.content_locale.to_translate'));
        }

        return Field::select(self::CONTENT_LOCALE)
            ->label(__('partner::partner.content_locale.label'))
            ->help(__('partner::partner.content_locale.help'))
            ->options($options)
            ->default($this->locale())
            // Facultatif : une app plus ancienne ne l'envoie pas, et la langue du compte vaut.
            ->reloads();
    }

    public function title(): string
    {
        return $this->partner === null ? __('partner::partner.member.add') : __('partner::partner.member.edit');
    }

    /** La note, le titre, le sous-titre et la description centrés, comme le formulaire d'une offre. */
    public function titleAlign(): string
    {
        return 'center';
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

    /** La langue de saisie : celle choisie à la modification, sinon celle du compte. */
    public function locale(): string
    {
        $chosen = request()->input(self::CONTENT_LOCALE);
        if (is_string($chosen) && in_array($chosen, Locales::values(), true) && $this->hasContentLocale()) {
            return $chosen;
        }

        return $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
    }
}
