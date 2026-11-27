<?php

namespace Modules\Offer\Forms;

use App\Support\Currency;
use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Locales;
use Bza\Form\Support\Step;
use Bza\Form\Support\Submission;
use Closure;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;
use Modules\Offer\Models\OfferCategory;
use Modules\Offer\Models\OfferInformation;
use Modules\Offer\Services\OfferPhotoService;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * Une offre du compte, depuis « Mes logements » (ou le libellé du domaine) :
 * vide pour en ajouter une (`POST /api/v1/offers`), ou liée à l'une des siennes
 * (`?record=12`) pour la modifier (`PATCH /api/v1/offers/12`). Champs communs,
 * puis les attributs du domaine actif (décision 2026-11-18-offres-et-domaines).
 * Les textes se saisissent dans la langue du compte, préremplis dans cette langue
 * ou, à défaut, une autre. Sans domaine choisi, le formulaire n'existe pas.
 */
class OfferForm extends Form
{
    private ?Offer $offer = null;

    public static function name(): string
    {
        return 'offer.offer';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    /**
     * Seule une offre du compte peut être ouverte ; une autre répond 404.
     */
    public function forRecord(string $key): static
    {
        $this->offer = Offer::query()
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey())
            ->whereKey($key)->first() ?? abort(404);

        return $this;
    }

    public function fields(): array
    {
        $domain = $this->domain();
        $offer = $this->offer;
        // Sans « Règles » ni « Caractéristiques », des cases à part (décision 2026-11-25-cases-par-categories).
        $categories = OfferCategory::options(withoutRoots: $domain->flagRoots());

        return array_values(array_filter([
            // La langue de saisie, à l'édition seulement et si l'app a plusieurs langues
            // (décision 2026-11-19-contrat-langue-saisie-allemand) : la changer recharge le formulaire.
            $this->contentLocaleField(),
            Field::text('title')
                ->label(__('offer::offer.title_field'))
                ->default($offer?->getTranslation('title', $this->locale()))
                ->required()
                // 50 caractères au plus (décision 2026-11-25-titre-offre-50).
                ->maxLength(Offer::TITLE_MAX_LENGTH),
            Field::textarea('description')
                ->label(__('offer::offer.description'))
                ->default($offer?->getTranslation('description', $this->locale()))
                ->maxLength(Field::TEXTAREA_MAX_LENGTH),
            $categories === [] ? null : Field::select('category_id')
                ->label(__('offer::offer.category'))
                ->options($categories)
                ->default($offer?->category_id === null ? null : (string) $offer->category_id),
            ! $domain->hasPrice() ? null : $this->price($domain, (string) Currency::symbol(CurrentCountry::require()->currency))
                ->default($offer?->price)
                ->required($domain->requiresPrice())
                ->rules(['min:0', 'max:100000000']),
            // L'adresse saisie (décision 2026-11-19-contrat-adresse-offre-recherche) :
            // la rue propose les adresses existantes du pays ; à l'enregistrement, une
            // adresse identique est reprise, sinon créée. Jamais le carnet du compte.
            ! $domain->hasAddress() ? null : Field::text('street')
                ->label(__('offer::offer.street'))
                ->help(__('offer::offer.street_help'))
                ->suggestions('api.v1.addresses.suggestions')
                ->default($offer?->address?->street)
                ->required($domain->requiresAddress())
                ->maxLength(255)
                ->rules(['required_with:city']),
            ! $domain->hasAddress() ? null : Field::text('zip')
                ->label(__('offer::offer.zip'))
                ->default($offer?->address?->zip ?: null)
                ->maxLength(20),
            // La région, avant la ville (décision 2026-11-25-region-avant-ville), obligatoire avec l'adresse ; une liste cherchable des régions actives du pays
            // (décision 2026-11-25-region-de-l-adresse).
            ! $domain->hasAddress() ? null : Field::select('region')
                // Son nom dans le pays : « Canton », « District »… (décision 2026-11-25-regions-par-pays).
                ->label($this->regionCountry($offer)->regionLabel())
                ->options($this->regionOptions($offer))
                ->searchable()
                ->default($offer?->address?->region_id === null ? null : (string) $offer->address->region_id)
                ->required($domain->requiresAddress())
                ->rules(['required_with:street']),
            ! $domain->hasAddress() ? null : Field::text('city')
                ->label(__('offer::offer.city'))
                ->default($offer?->address?->city)
                ->required($domain->requiresAddress())
                ->maxLength(255)
                ->rules(['required_with:street']),
            // L'indication propre à l'offre, l'adresse étant partagée (décision 2026-11-19-contrat-adresse-offre-table).
            ! $domain->hasAddress() ? null : Field::textarea('indication')
                ->label(__('offer::offer.indication'))
                ->placeholder(__('offer::offer.indication_placeholder'))
                // Traduite (décision 2026-11-19-indication-traduisible) : la langue de saisie, sinon l'original.
                ->translatable()
                ->default($offer?->offerAddress?->getTranslation('indication', $this->locale()) ?: null)
                ->maxLength(OfferAddress::INDICATION_MAX_LENGTH),
            // Afficher l'adresse ou non : sinon, la fiche n'a pas de bloc « Localisation » (décision
            // 2026-11-21-afficher-adresse) ; chaque demande part de ce choix.
            ! $domain->hasAddress() ? null : Field::switch('show_address')
                ->label(__('offer::offer.show_address'))
                ->help(__('offer::offer.show_address_help'))
                ->default($offer === null ? true : $offer->show_address),
            // Le téléphone et l'e-mail de contact de l'offre, facultatifs : montrés au membre qui la demande ;
            // vides, ceux du compte (décision 2026-11-21-contact-offre).
            Field::phone('phone')
                ->label(__('offer::offer.phone'))
                ->help(__('offer::offer.contact_help'))
                ->options(PhoneNumbers::countryOptions())
                ->default($offer?->phone === null
                    ? ['country' => CurrentCountry::get()?->code, 'number' => null]
                    : ['country' => (new PhoneNumber($offer->phone))->getCountry(), 'number' => PhoneNumbers::national($offer->phone)]),
            Field::email('email')
                ->label(__('offer::offer.email'))
                ->help(__('offer::offer.contact_help'))
                ->default($offer?->email)
                ->rules(['nullable', 'max:255']),
            Field::select('status')
                ->label(__('offer::offer.status'))
                ->options(OfferStatusEnum::options())
                ->default(($offer?->status ?? OfferStatusEnum::DRAFT)->value)
                ->required()
                // Pas de changement de statut tant qu'il a une demande en cours (décision
                // 2026-11-22-logement-verrouille-par-demandes).
                ->rules([function (string $attribute, mixed $value, Closure $fail) use ($offer): void {
                    // Revenir à « Publiée » reste toujours possible.
                    if ($offer !== null && $value !== $offer->status->value && $value !== OfferStatusEnum::PUBLISHED->value && $offer->hasOpenBookings()) {
                        $fail(__('offer::offer.status_locked'));
                    }
                }]),
            // Les cases d'une catégorie : les ids enregistrés qui existent encore (décision 2026-11-25-categories-slug-traduit).
            ...array_map(fn (Field $field): Field => $field->default(array_key_exists($field->name, $domain->flagGroups())
                ? $domain->checkedFlags($offer, $field->name)
                : $offer?->detailIn($field->name, $this->locale())), $domain->inputAttributes(CurrentCountry::require()->currency)),
            // Les blocs d'informations, ajoutés et retirés par le propriétaire (décision 2026-11-19-contrat-offer-informations).
            Field::repeater('informations')
                ->label(__('offer::offer.informations.label'))
                // Le titre de l'étape « Informations » suffit (décision 2026-11-19-etape-informations).
                ->hideLabel()
                ->help(__('offer::offer.informations.help'))
                ->addLabel(__('offer::offer.informations.add'))
                ->maxItems(OfferInformation::MAX_PER_OFFER)
                ->fields([
                    // L'identifiant d'un bloc enregistré : ses autres langues sont gardées.
                    Field::hidden('id'),
                    Field::text('title')
                        ->label(__('offer::offer.informations.title'))
                        ->placeholder(__('offer::offer.informations.title_placeholder'))
                        ->required()
                        ->maxLength(OfferInformation::TITLE_MAX_LENGTH),
                    Field::textarea('description')
                        ->label(__('offer::offer.informations.description'))
                        ->required()
                        ->maxLength(OfferInformation::DESCRIPTION_MAX_LENGTH),
                ])
                ->default($offer === null ? [] : $offer->informations->map(fn (OfferInformation $information): array => [
                    'id' => (string) $information->getKey(),
                    'title' => (string) $information->getTranslation('title', $this->locale()),
                    'description' => (string) $information->getTranslation('description', $this->locale()),
                ])->values()->all()),
            // Les photos, au moins une (décision 2026-11-19-contrat-champ-upload-photos).
            Field::upload('photos')
                ->label(__('offer::offer.photos'))
                // Le titre de l'étape « Photos » suffit (décision 2026-11-19-nuit-ordre-photos-libelle).
                ->hideLabel()
                ->help(__('offer::offer.photos_help', ['pixels' => OfferPhotoService::minUploadSize()]))
                ->accept(OfferPhotoService::acceptedMimeTypes())
                ->maxSize(OfferPhotoService::maxUploadKb())
                // Au moins la taille enregistrée (décision 2026-11-24-image-taille-minimale-enregistree).
                ->uploadRules(OfferPhotoService::uploadRules())
                ->maxFiles(OfferPhotoService::maxFiles())
                ->files(app(OfferPhotoService::class)->current($offer))
                ->required(),
        ]));
    }

    /**
     * Le prix, avec le symbole de sa devise et son unité ; l'unité peut suivre un
     * autre champ (« FCFA / jour » si meublé, décisions 2026-11-18-unite-prix-chambres
     * et 2026-11-19-contrat-formulaire-etapes-symbole-devise).
     */
    private function price(Domain $domain, string $currency): Field
    {
        $field = Field::number('price')->label($domain->priceLabel());
        $unit = $domain->priceUnitWhen();

        if ($unit === null) {
            return $field->suffix($currency);
        }

        return $field
            ->suffix($currency.' / '.$unit['default'])
            ->suffixWhen($unit['field'], array_map(fn (string $value): string => $currency.' / '.$value, $unit['units']));
    }

    /**
     * Les étapes déclarées par le domaine (décision
     * 2026-11-19-contrat-formulaire-etapes-symbole-devise), réduites aux champs
     * présents ; un champ qu'aucune étape ne cite rejoint la dernière. Sans étapes
     * déclarées, le formulaire reste d'un seul tenant.
     *
     * @return list<Step>
     */
    public function steps(): array
    {
        $declared = $this->domain()->formSteps();

        if ($declared === []) {
            return [];
        }

        // L'étape « Langue » en premier, quand elle existe (décision 2026-11-19-contrat-langue-saisie-allemand).
        if ($this->hasContentLocale()) {
            $declared = [['title' => __('offer::offer.content_locale.step'), 'fields' => [self::CONTENT_LOCALE]], ...$declared];
        }

        $present = array_map(fn (Field $field): string => $field->name, $this->fields());
        $steps = [];
        $placed = [];

        foreach ($declared as $step) {
            $names = array_values(array_intersect($step['fields'], $present));
            $placed = [...$placed, ...$names];
            $steps[] = ['title' => $step['title'], 'fields' => $names];
        }

        $last = count($steps) - 1;
        $steps[$last]['fields'] = [...$steps[$last]['fields'], ...array_values(array_diff($present, $placed))];

        return array_values(array_map(
            fn (array $step): Step => Step::make($step['title'], $step['fields']),
            array_filter($steps, fn (array $step): bool => $step['fields'] !== []),
        ));
    }

    /**
     * Titres d'étape centrés (décision 2026-11-19-alignement-titre-formulaire).
     */
    public function titleAlign(): string
    {
        return 'center';
    }

    /**
     * Les régions actives du pays de l'offre (sinon du pays courant), dans l'ordre du back-office : id => nom dans la
     * langue de l'app (décision 2026-11-25-region-de-l-adresse).
     *
     * @return array<string, string>
     */
    private function regionOptions(?Offer $offer): array
    {
        return Region::optionsIn($this->regionCountry($offer)->getKey());
    }

    /** Le pays de l'offre, sinon le pays courant : celui de ses régions. */
    private function regionCountry(?Offer $offer): Country
    {
        return $offer?->country ?? CurrentCountry::require();
    }

    public function title(): string
    {
        return __('offer::offer.form.'.($this->offer === null ? 'create_title' : 'edit_title'), ['label' => $this->domain()->label()]);
    }

    public function submission(): Submission
    {
        $message = __('offer::offer.form.saved');

        return $this->offer === null
            ? Submission::toRoute('api.v1.offers.store', SuccessAction::BACK, $message)
            : Submission::toRoute('api.v1.offers.update', SuccessAction::BACK, $message, parameters: ['offer' => $this->offer->getKey()]);
    }

    public function submitLabel(): string
    {
        return __('offer::offer.form.save');
    }

    /**
     * La langue de saisie des textes : celle choisie à l'étape « Langue » (paramètre
     * ou champ `content_locale`), sinon la langue du compte.
     */
    public function locale(): string
    {
        $chosen = request()->input(self::CONTENT_LOCALE);

        if (is_string($chosen) && in_array($chosen, Locales::values(), true) && $this->hasContentLocale()) {
            return $chosen;
        }

        return $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
    }

    /** Le champ de la langue de saisie. */
    public const CONTENT_LOCALE = 'content_locale';

    /**
     * L'étape « Langue » existe-t-elle ? À l'édition, quand l'app propose plusieurs langues.
     */
    private function hasContentLocale(): bool
    {
        return $this->offer !== null && count(Locales::values()) > 1;
    }

    /**
     * « Dans quelle langue modifiez-vous cette annonce ? » : chaque langue avec son
     * état (langue d'origine, traduite, à traduire) ; la choisir recharge le formulaire.
     */
    private function contentLocaleField(): ?Field
    {
        if (! $this->hasContentLocale()) {
            return null;
        }

        $offer = $this->offer;
        $titles = array_keys(array_filter($offer?->getTranslations('title') ?? [], fn (mixed $title): bool => filled($title)));
        $options = [];

        foreach (Locales::options() as $code => $label) {
            $state = match (true) {
                $code === $offer?->locale => __('offer::offer.content_locale.original'),
                in_array($code, $titles, true) => __('offer::offer.content_locale.translated'),
                default => __('offer::offer.content_locale.to_translate'),
            };
            $options[$code] = $label.' — '.$state;
        }

        return Field::select(self::CONTENT_LOCALE)
            ->label(__('offer::offer.content_locale.label'))
            ->help(__('offer::offer.content_locale.help'))
            ->options($options)
            ->default($this->locale())
            // Facultatif : une app plus ancienne ne l'envoie pas, et la langue du compte vaut.
            ->reloads();
    }

    public function domain(): Domain
    {
        return app(DomainRegistry::class)->require();
    }
}
