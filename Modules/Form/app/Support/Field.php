<?php

namespace Modules\Form\Support;

use App\Support\Locales;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use LogicException;
use Modules\Form\Enums\FieldType;
use Modules\Form\Rules\NoContactDetails;
use Modules\Form\Rules\OptionBelongsToParent;

/**
 * Un champ de formulaire : ce que l'app affiche (libellé, type, options…) et
 * ce que le serveur valide, déclarés au même endroit.
 */
final class Field
{
    private ?string $label = null;

    private ?string $placeholder = null;

    private ?string $help = null;

    private bool $required = false;

    /** Affiché, non modifiable, jamais validé ni enregistré. */
    private bool $readonly = false;

    /** Liste dépendante : nom du champ parent (ex. le pays d'une région). */
    private ?string $dependsOn = null;

    private mixed $default = null;

    /**
     * Affiché seulement quand un autre champ a une valeur donnée ; masqué, il
     * n'est ni validé ni pris en compte.
     *
     * @var array{field: string, value: string}|null
     */
    private ?array $visibleWhen = null;

    /** Texte sur plusieurs lignes : nombre maximal de caractères. */
    public const TEXTAREA_MAX_LENGTH = 1000;

    private ?int $maxLength = null;

    /** Unité affichée après la valeur (« m² ») : formulaire et fiche (décision 2026-11-18-fiche-offre). */
    private ?string $suffix = null;

    /**
     * Unité qui suit la valeur d'un autre champ (décision 2026-11-18-unite-prix-chambres) :
     * `{field: 'furnished', suffixes: {'true': 'FCFA / jour', 'false': 'FCFA / mois'}}`.
     *
     * @var array{field: string, suffixes: array<string, string>}|null
     */
    private ?array $suffixWhen = null;

    /** Texte rangé par langue (décision 2026-11-18-offres-multilingues-categories-arbre). */
    private bool $translatable = false;

    /** Montant dans une devise (`money()`) : son unité de temps éventuelle (« mois »). */
    private bool $money = false;

    private ?string $moneyPer = null;

    /** Texte refusé s'il contient une coordonnée (`withoutContactDetails()`). */
    private bool $withoutContactDetails = false;

    /**
     * valeur => libellé, ou valeur => [libellé, indication, parent], dans l'ordre
     * d'affichage. L'indication complète le libellé (ex. l'indicatif d'un pays) ;
     * le parent sert aux listes dépendantes.
     *
     * @var array<string, string|array{label: string, hint?: string|null, parent?: string|int|null}>
     */
    private array $options = [];

    /** @var list<mixed> */
    private array $rules = [];

    private function __construct(
        public readonly string $name,
        public readonly FieldType $type,
    ) {}

    public static function make(string $name, FieldType $type): self
    {
        return new self($name, $type);
    }

    public static function segmented(string $name): self
    {
        return new self($name, FieldType::SEGMENTED);
    }

    public static function text(string $name): self
    {
        return new self($name, FieldType::TEXT);
    }

    public static function hidden(string $name): self
    {
        return new self($name, FieldType::HIDDEN);
    }

    public static function email(string $name): self
    {
        return new self($name, FieldType::EMAIL);
    }

    public static function password(string $name): self
    {
        return new self($name, FieldType::PASSWORD);
    }

    public static function number(string $name): self
    {
        return new self($name, FieldType::NUMBER);
    }

    /**
     * Texte sur plusieurs lignes, limité à `TEXTAREA_MAX_LENGTH` caractères sauf
     * `maxLength()` contraire.
     */
    public static function textarea(string $name): self
    {
        return (new self($name, FieldType::TEXTAREA))->maxLength(self::TEXTAREA_MAX_LENGTH);
    }

    public static function select(string $name): self
    {
        return new self($name, FieldType::SELECT);
    }

    public static function radio(string $name): self
    {
        return new self($name, FieldType::RADIO);
    }

    public static function checkbox(string $name): self
    {
        return new self($name, FieldType::CHECKBOX);
    }

    public static function checkboxGroup(string $name): self
    {
        return new self($name, FieldType::CHECKBOX_GROUP);
    }

    public static function date(string $name): self
    {
        return new self($name, FieldType::DATE);
    }

    /**
     * Téléphone : un pays (son indicatif) et un numéro, sur une ligne. Valeurs
     * envoyées : `<nom>` (numéro) et `<nom>_country` (code ISO du pays). Les options
     * sont les pays proposés ; la valeur par défaut est `['country' => …, 'number' => …]`.
     */
    public static function phone(string $name): self
    {
        return new self($name, FieldType::PHONE);
    }

    /**
     * Code de vérification à 6 chiffres.
     */
    public static function code(string $name): self
    {
        return new self($name, FieldType::CODE);
    }

    /**
     * Note en étoiles, de 1 à 5 : cinq étoiles dans l'app, un choix de 1 à 5 dans
     * le back-office (décision 2026-11-19-contrat-avis-offres).
     */
    public static function rating(string $name): self
    {
        return new self($name, FieldType::RATING);
    }

    public static function switch(string $name): self
    {
        return new self($name, FieldType::SWITCH);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function help(?string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * Liste dépendante : seules les options dont le parent vaut la valeur du champ
     * `$field` sont proposées et acceptées.
     */
    public function dependsOn(string $field): self
    {
        $this->dependsOn = $field;

        return $this;
    }

    /**
     * N'afficher (et ne valider) ce champ que si `$field` vaut `$value`.
     */
    /**
     * La condition d'affichage (`visibleWhen()`), ou `null`.
     *
     * @return array{field: string, value: string}|null
     */
    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhen;
    }

    /**
     * Le champ s'affiche-t-il avec ces valeurs (par nom) ? Une case vaut « true » ou
     * « false » (décision 2026-11-19-charges-meuble-surface-facultative).
     *
     * @param  array<string, mixed>  $values
     */
    public function isVisibleFor(array $values): bool
    {
        if ($this->visibleWhen === null) {
            return true;
        }

        return self::conditionValue($values[$this->visibleWhen['field']] ?? null) === $this->visibleWhen['value'];
    }

    /**
     * Une valeur telle qu'une condition la compare : « true »/« false » pour une case.
     */
    public static function conditionValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            default => (string) $value,
        };
    }

    public function visibleWhen(string $field, string $value): self
    {
        $this->visibleWhen = ['field' => $field, 'value' => $value];

        return $this;
    }

    /**
     * Une liste (ou le pays d'un téléphone) qui n'a qu'une option : elle est
     * choisie d'office et ne peut pas changer. Contrairement à `readonly`, la
     * valeur est envoyée et validée (décision 2026-11-20-pays-unique-identifiant-double).
     */
    public function isLocked(): bool
    {
        return in_array($this->type, [FieldType::SELECT, FieldType::PHONE], true)
            && $this->dependsOn === null
            && count($this->options) === 1;
    }

    /**
     * Valeur initiale envoyée à l'app : l'option unique d'un champ verrouillé
     * quand rien d'autre n'est choisi.
     */
    private function initialValue(): mixed
    {
        if (! $this->isLocked()) {
            return $this->default;
        }

        $only = (string) array_key_first($this->options);

        if ($this->type === FieldType::PHONE) {
            return ['country' => $only, 'number' => is_array($this->default) ? ($this->default['number'] ?? null) : null];
        }

        return $only;
    }

    /**
     * Nombre maximal de caractères : validé par le serveur, envoyé à l'app (qui
     * bloque la saisie au-delà et affiche un compteur).
     */
    public function maxLength(?int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;

        return $this;
    }

    /**
     * @param  array<string, string|array{label: string, hint?: string|null, parent?: string|int|null}>  $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Règles propres au champ, en plus de celles qui découlent de son type et
     * de `required()` (ex. `max:255`).
     *
     * @param  list<mixed>  $rules
     */
    /**
     * Texte publié : refusé s'il contient une coordonnée ou un indice de contact
     * (décision 2026-11-19-contrat-annonces-sans-coordonnees). Texte court ou long seulement.
     */
    public function withoutContactDetails(bool $enabled = true): self
    {
        if ($enabled && ! in_array($this->type, [FieldType::TEXT, FieldType::TEXTAREA], true)) {
            throw new LogicException("Champ « {$this->name} » : seul un texte se contrôle pour les coordonnées.");
        }

        $this->withoutContactDetails = $enabled;

        return $this;
    }

    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Règles de validation complètes du champ, par nom (un groupe de cases ajoute
     * la règle de chacun de ses éléments).
     *
     * @return array<string, list<mixed>>
     */
    public function validationRules(): array
    {
        // Lecture seule : rien n'est attendu ni pris en compte pour ce champ.
        if ($this->readonly) {
            return [];
        }

        if ($this->visibleWhen === null) {
            return $this->ownRules();
        }

        // Masqué par sa condition : exclu de la validation et des données validées.
        $condition = 'exclude_unless:'.$this->visibleWhen['field'].','.$this->visibleWhen['value'];

        return array_map(fn (array $rules): array => [$condition, ...$rules], $this->ownRules());
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function ownRules(): array
    {
        $values = array_map('strval', array_keys($this->options));
        $presence = $this->required ? ['required'] : ['nullable'];

        if ($this->type === FieldType::PHONE) {
            // Le numéro est vérifié selon le pays choisi (`<nom>_country`, code ISO).
            return [
                $this->name => [...$presence, 'string', 'phone', ...$this->rules],
                $this->name.'_country' => [$this->required ? 'required' : 'required_with:'.$this->name, 'nullable', 'string', Rule::in($values)],
            ];
        }

        if ($this->dependsOn !== null) {
            $parents = collect($this->options)
                ->map(fn (string|array $option): ?string => is_array($option) && isset($option['parent']) ? (string) $option['parent'] : null)
                ->all();

            return [$this->name => [...$presence, ...$this->type->rules($values), new OptionBelongsToParent($this->dependsOn, $parents), ...$this->rules]];
        }

        if ($this->type === FieldType::CHECKBOX_GROUP) {
            return [
                $this->name => [...$presence, 'array', ...$this->rules],
                $this->name.'.*' => ['string', Rule::in($values)],
            ];
        }

        $length = $this->maxLength === null ? [] : ['max:'.$this->maxLength];
        $contact = $this->withoutContactDetails ? [new NoContactDetails] : [];

        return [$this->name => [...$presence, ...$this->type->rules($values), ...$length, ...$contact, ...$this->rules]];
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Texte traduisible : rangé par langue, saisi dans la langue du compte dans
     * l'app, par onglet de langue dans le back-office. Texte court ou long seulement.
     */
    public function translatable(bool $translatable = true): self
    {
        if ($translatable && ! in_array($this->type, [FieldType::TEXT, FieldType::TEXTAREA], true)) {
            throw new LogicException("Champ « {$this->name} » : seul un texte peut être traduisible.");
        }

        $this->translatable = $translatable;

        return $this;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    /**
     * Un montant, dans une devise que le champ ne connaît pas (celle du pays ou de
     * l'offre) ; `$per` : son unité de temps (« mois »). Qui connaît la devise
     * l'ajoute avec `inCurrency()` : l'unité devient « FCFA / mois » (décision
     * 2026-11-19-contrat-formulaire-etapes-symbole-devise).
     */
    public function money(?string $per = null): self
    {
        $this->money = true;
        $this->moneyPer = $per;

        return $this;
    }

    /**
     * L'unité d'un montant (`money()`) dans la devise de symbole `$symbol` ; sans
     * effet sur un autre champ, ou sans symbole.
     */
    public function inCurrency(?string $symbol): self
    {
        if ($this->money && filled($symbol)) {
            $this->suffix = $this->moneyPer === null ? $symbol : $symbol.' / '.$this->moneyPer;
        }

        return $this;
    }

    /**
     * Unité affichée après la valeur (« m² »).
     */
    public function suffix(?string $suffix): self
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Unité qui dépend de la valeur du champ `$field` (« true », « false » pour une
     * case) ; `suffix()` reste l'unité par défaut.
     *
     * @param  array<string, string>  $suffixes  valeur => unité
     */
    public function suffixWhen(string $field, array $suffixes): self
    {
        $this->suffixWhen = ['field' => $field, 'suffixes' => $suffixes];

        return $this;
    }

    /**
     * La valeur, lisible par une personne, dans la langue `$locale` (fiche d'une
     * offre, décision 2026-11-18-fiche-offre) : le libellé d'un choix, « Oui » ou
     * « Non », un nombre ou une date au format local, l'unité éventuelle ; un
     * texte traduisible dans sa langue. `null` : rien à afficher.
     */
    public function displayValue(mixed $value, string $locale): ?string
    {
        if ($this->translatable && is_array($value)) {
            $value = $value[$locale] ?? $value[Locales::default()] ?? (reset($value) ?: null);
        }

        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $labels = $this->optionLabels();
        $text = match ($this->type) {
            FieldType::SELECT, FieldType::RADIO, FieldType::SEGMENTED => $labels[(string) $value] ?? (string) $value,
            FieldType::CHECKBOX_GROUP => implode(', ', array_map(fn (mixed $item): string => $labels[(string) $item] ?? (string) $item, (array) $value)),
            FieldType::CHECKBOX, FieldType::SWITCH => __('form::field.'.(filter_var($value, FILTER_VALIDATE_BOOL) ? 'yes' : 'no'), [], $locale),
            FieldType::RATING => is_numeric($value) ? str_repeat('★', $stars = max(0, min(5, (int) $value))).str_repeat('☆', 5 - $stars) : (string) $value,
            FieldType::NUMBER => is_numeric($value) ? (string) Number::format((float) $value, locale: $locale) : (string) $value,
            FieldType::DATE => Carbon::parse((string) $value)->locale($locale)->isoFormat('LL'),
            default => is_scalar($value) ? (string) $value : null,
        };

        return $text === null || $text === '' ? null : trim($text.' '.($this->suffix ?? ''));
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * valeur => libellé, pour un back-office ou tout autre affichage hors de l'app.
     *
     * @return array<string, string>
     */
    public function optionLabels(): array
    {
        return collect($this->options)
            ->mapWithKeys(fn (string|array $option, string|int $value): array => [(string) $value => is_array($option) ? $option['label'] : $option])
            ->all();
    }

    /**
     * Ce que l'app reçoit pour afficher le champ.
     *
     * @return array{name: string, type: string, label: string|null, placeholder: string|null, help: string|null, required: bool, readonly: bool, locked: bool, depends_on: string|null, visible_when: array{field: string, value: string}|null, max_length: int|null, suffix: string|null, suffix_when: array{field: string, suffixes: array<string, string>}|null, default: mixed, options: list<array{value: string, label: string, hint: string|null, parent: string|null}>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'help' => $this->help,
            'required' => $this->required,
            'readonly' => $this->readonly,
            /** Option unique, choisie d'office : non modifiable, mais envoyée. */
            'locked' => $this->isLocked(),
            'depends_on' => $this->dependsOn,
            /** Afficher seulement quand le champ `field` vaut `value`. */
            'visible_when' => $this->visibleWhen,
            'max_length' => $this->maxLength,
            /** Unité affichée après la valeur (« m² »). */
            'suffix' => $this->suffix,
            /** Unité qui suit la valeur d'un autre champ (`suffixes` : valeur => unité) ; `suffix` à défaut. */
            'suffix_when' => $this->suffixWhen,
            'default' => $this->initialValue(),
            'options' => collect($this->options)
                ->map(fn (string|array $option, string|int $value): array => [
                    'value' => (string) $value,
                    'label' => is_array($option) ? $option['label'] : $option,
                    'hint' => is_array($option) ? ($option['hint'] ?? null) : null,
                    'parent' => is_array($option) && isset($option['parent']) ? (string) $option['parent'] : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
