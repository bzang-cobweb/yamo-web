<?php

namespace Modules\Partner\Forms;

use App\Support\Locales;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Step;
use Modules\Form\Support\Submission;
use Modules\Location\Support\CurrentCountry;
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
     * Un partenaire que le compte peut modifier (`PartnerPolicy::update` : rattaché, dans le pays courant) ; sinon
     * 404 (décision 2026-11-24-partenaires-policy-routes).
     */
    public function forRecord(string $key): static
    {
        $partner = Partner::query()->find($key) ?? abort(404);
        Gate::forUser($this->user)->authorize('update', $partner);
        $this->partner = $partner;

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
                ->accept(PartnerLogoService::acceptedMimeTypes())
                ->maxSize(PartnerLogoService::maxUploadKb())
                ->maxFiles(1)
                ->minImageSide(PartnerLogoService::minUploadSize())
                ->help(__('partner::partner.fields.logo_help', ['pixels' => PartnerLogoService::minUploadSize()]))
                ->addLabel(__('partner::partner.member.add_logo'))
                ->files(app(PartnerLogoService::class)->current($partner)),
            Field::text('name')
                ->label(__('partner::partner.fields.name'))
                ->required()
                ->maxLength(255)
                ->default($text('name')),
            // Facultatif, dans la langue de saisie (décision 2026-11-24-partenaire-slogan).
            Field::text('slogan')
                ->label(__('partner::partner.fields.slogan'))
                ->maxLength(120)
                ->default($text('slogan')),
            // Obligatoire ; le logo, lui, est facultatif (décision 2026-11-24-partenaire-description-obligatoire).
            Field::textarea('description')
                ->label(__('partner::partner.fields.description'))
                ->required()
                ->maxLength(500)
                ->default($text('description')),
            // L'indicatif du pays du partenaire, seul proposé : choisi d'office et verrouillé (décision
            // 2026-11-24-partenaire-telephone-pays).
            Field::phone('contact_phone')
                ->label(__('partner::partner.fields.contact_phone'))
                ->options($this->phoneCountry())
                ->default(['country' => array_key_first($this->phoneCountry()), 'number' => $partner?->contact_phone === null ? null : PhoneNumbers::national($partner->contact_phone)]),
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
            Step::make(__('partner::partner.member.steps.presentation'), [PartnerLogoService::FIELD, 'name', 'slogan', 'description']),
            Step::make(__('partner::partner.sections.contact'), ['contact_phone', 'contact_email', 'url', 'cta_label']),
        ]));
    }

    /**
     * Le pays du téléphone : celui du partenaire, sinon le pays courant (un nouveau partenaire y est créé).
     *
     * @return array<string, array{label: string, hint: string}>
     */
    private function phoneCountry(): array
    {
        $country = $this->partner?->country ?? CurrentCountry::require();

        return [$country->code => ['label' => $country->label(), 'hint' => (string) $country->dial_code]];
    }

    /** Le champ de la langue de saisie, comme pour une offre. */
    public const CONTENT_LOCALE = 'content_locale';

    /**
     * L'étape « Langue » existe-t-elle ? À la modification, quand l'app propose plusieurs langues.
     */
    private function hasContentLocale(): bool
    {
        if ($this->partner === null) {
            return false;
        }

        return count(Locales::values()) > 1;
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
        $account = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
        if (! $this->hasContentLocale()) {
            return $account;
        }

        $chosen = request()->input(self::CONTENT_LOCALE);
        if (! is_string($chosen) || ! in_array($chosen, Locales::values(), true)) {
            return $account;
        }

        return $chosen;
    }
}
