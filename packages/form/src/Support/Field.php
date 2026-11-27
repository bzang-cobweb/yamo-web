<?php

namespace Bza\Form\Support;

use Bza\Form\Support\Locales;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LogicException;
use Bza\Form\Enums\FieldType;
use Bza\Form\Models\FormUpload;
use Bza\Form\Rules\NoContactDetails;
use Bza\Form\Rules\OptionBelongsToParent;

/**
 * A form field: what the app displays (label, type, options…) and
 * what the server validates, declared in one place.
 */
final class Field
{
    private ?string $label = null;

    private ?string $placeholder = null;

    private ?string $help = null;

    /** A note before the label: a warning (decision 2026-11-22-champ-note). */
    private ?string $notice = null;

    private bool $required = false;

    /** Label not displayed by the app (`hideLabel()`); read by accessibility tools, it is used in messages. */
    private bool $labelHidden = false;

    /** Displayed, not editable, never validated or saved. */
    private bool $readonly = false;

    /** Dropdown list: a search box above the options, whatever their number. */
    private bool $searchable = false;

    /** A list filter without the « Tous » option (decision 2026-11-25-region-sans-tous). */
    private bool $withoutAllOption = false;

    /** A country-specific filter (region, city…), reset when the country changes (decision 2026-11-25-region-en-tete-recherche). */
    private bool $countryScoped = false;

    /** Dependent list: name of the parent field (e.g. the country of a region). */
    private ?string $dependsOn = null;

    /** A filter: never locked, « Tous » stays available (decision 2026-11-20-filtre-option-unique). */
    private bool $filter = false;

    private mixed $default = null;

    /**
     * Displayed only when another field has a given value; when hidden, it is
     * neither validated nor taken into account. `values`: the values that show it
     * (`value`, the first one, kept for clients that read a single value).
     *
     * @var array{field: string, value: string, values: list<string>}|null
     */
    private ?array $visibleWhen = null;

    /** Multi-line text: maximum number of characters. */
    public const TEXTAREA_MAX_LENGTH = 1000;

    private ?int $maxLength = null;

    /** Bounds of a date (`YYYY-MM-DD`), offered by the app's calendar; `null`: unbounded. */
    private ?string $dateMin = null;

    /**
     * Numeric bounds and step of a rating or a slider range (decisions 2026-11-27-note-bornes-etoiles
     * and 2026-11-27-intervalle-curseur); `null`: no slider (a range with two inputs).
     */
    private ?float $boundMin = null;

    private ?float $boundMax = null;

    private ?float $boundStep = null;

    private bool $ratingStars = true;

    private ?string $dateMax = null;

    /** @var array{unavailable: list<string>, after: string|null}|null */
    private ?array $calendar = null;

    /** Unit displayed after the value (« m² »): form and detail page (decision 2026-11-18-fiche-offre). */
    private ?string $suffix = null;

    /**
     * Unit that follows the value of another field (decision 2026-11-18-unite-prix-chambres):
     * `{field: 'rental_type', suffixes: {'furnished': 'FCFA / jour', 'long_term': 'FCFA / mois'}}`.
     *
     * @var array{field: string, suffixes: array<string, string>}|null
     */
    private ?array $suffixWhen = null;

    /** Text stored per language (decision 2026-11-18-offres-multilingues-categories-arbre). */
    private bool $translatable = false;

    /** Amount in a currency (`money()`): its optional time unit (« mois »). */
    private bool $money = false;

    private ?string $moneyPer = null;

    /** Suggestions while typing: relative API endpoint (`/v1/…`), or none. */
    private ?string $suggestions = null;

    /**
     * Files (`upload()`): accepted MIME types, maximum size of one file (KB),
     * maximum number of files, and files already saved (identifier => URL).
     *
     * @var list<string>
     */
    private array $accept = [];

    private int $maxSizeKb = 10240;

    private int $maxFiles = 1;

    /**
     * Additional rules for each uploaded file (`uploadRules()`): an image's minimum.
     *
     * @var list<mixed>
     */
    private array $uploadRules = [];

    /** @var array<string, string> */
    private array $files = [];

    /**
     * List of groups (`repeater()`): the sub-fields of a group, the maximum number
     * of groups and the add button label.
     *
     * @var list<Field>
     */
    private array $itemFields = [];

    private int $maxItems = 10;

    private ?string $addLabel = null;

    /** Changing the value reloads the form with `?<name>=<value>` (`reloads()`). */
    private bool $reloads = false;

    /** Text rejected if it contains contact details (`withoutContactDetails()`). */
    private bool $withoutContactDetails = false;

    /**
     * value => label, or value => [label, hint, parent], in display
     * order. The hint complements the label (e.g. a country's dialing code);
     * the parent is used by dependent lists.
     *
     * @var array<string, string|array{label: string, hint?: string|null, parent?: string|int|null}>
     */
    private array $options = [];

    /** The options, computed on first read (decision 2026-11-25-cases-par-categories); `null`: `$options`. */
    private ?Closure $optionsResolver = null;

    /** The label, computed on first read; `null`: `$label`. */
    private ?Closure $labelResolver = null;

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
     * Multi-line text, limited to `TEXTAREA_MAX_LENGTH` characters unless
     * `maxLength()` says otherwise.
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

    /**
     * Multi-select dropdown list: the value is the list of selected options
     * (decision 2026-11-19-contrat-multiselect-intervalle-equipements).
     */
    public static function multiselect(string $name): self
    {
        return new self($name, FieldType::MULTISELECT);
    }

    /**
     * Multiple choice as chips on a scrolling row: the value is the list of selected options, like
     * `multiselect` (decision 2026-11-26-filtres-en-puces).
     */
    public static function chips(string $name): self
    {
        return new self($name, FieldType::CHIPS);
    }

    /**
     * Range: two bounds `[min, max]`, each optional; max ≥ min.
     * The optional unit (`suffix()`) applies to both.
     */
    public static function range(string $name): self
    {
        return new self($name, FieldType::RANGE);
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
     * Phone: a country (its dialing code) and a number, on one line. Values
     * sent: `<name>` (number) and `<name>_country` (country ISO code). The options
     * are the offered countries; the default value is `['country' => …, 'number' => …]`.
     */
    public static function phone(string $name): self
    {
        return new self($name, FieldType::PHONE);
    }

    /**
     * 6-digit verification code.
     */
    public static function code(string $name): self
    {
        return new self($name, FieldType::CODE);
    }

    /**
     * Star rating, from 1 to 5: five stars in the app, a choice from 1 to 5 in
     * the back office (decision 2026-11-19-contrat-avis-offres).
     */
    public static function rating(string $name): self
    {
        // From 1 to 5 in tenths by default (decision 2026-11-27-note-au-dixieme).
        return (new self($name, FieldType::RATING))->bounds(1, 5, 0.1);
    }

    /**
     * Files (decision 2026-11-19-contrat-champ-upload-photos): the app uploads
     * each file as soon as it is chosen (`POST /api/v1/forms/{form}/uploads/{champ}`),
     * validated by `fileRules()`, and receives its identifier; the field value is the
     * ordered list of identifiers, already saved files (`files()`) included.
     * Accepted types, size and maximum count are declared here and served to the app.
     */
    public static function upload(string $name): self
    {
        return new self($name, FieldType::UPLOAD);
    }

    /**
     * Accepted MIME types (`image/jpeg`…).
     *
     * @param  list<string>  $mimeTypes
     */
    public function accept(array $mimeTypes): self
    {
        $this->assertUpload();
        $this->accept = array_values($mimeTypes);

        return $this;
    }

    /** Maximum size of one file, in KB. */
    public function maxSize(int $kilobytes): self
    {
        $this->assertUpload();
        $this->maxSizeKb = $kilobytes;

        return $this;
    }

    /**
     * Additional rules for each uploaded file, after type and size: an image's minimum, the one from its
     * service (`ConfiguredImageService::uploadRules()`, decision 2026-11-24-image-regle-minimum-par-cadre).
     *
     * @param  list<mixed>  $rules
     */
    public function uploadRules(array $rules): self
    {
        $this->assertUpload();
        $this->uploadRules = $rules;

        return $this;
    }

    /** Maximum number of files (1: a single one). */
    public function maxFiles(int $count): self
    {
        $this->assertUpload();
        $this->maxFiles = max(1, $count);

        return $this;
    }

    /**
     * The files already saved, in order: identifier => URL. They are
     * the field's initial value, and their identifiers are accepted on submit.
     *
     * @param  array<string, string>  $files
     */
    public function files(array $files): self
    {
        $this->assertUpload();
        $this->files = $files;
        $this->default = array_map('strval', array_keys($files));

        return $this;
    }

    /**
     * Rules for a file uploaded for this field: type, size; an oversized image
     * to decode is rejected.
     *
     * @return list<mixed>
     */
    public function fileRules(): array
    {
        $this->assertUpload();
        $images = $this->accept !== [] && array_filter($this->accept, fn (string $type): bool => ! str_starts_with($type, 'image/')) === [];

        return [
            // A single message per rejection: the first that applies.
            'bail',
            'required',
            'file',
            ...($this->accept === [] ? [] : ['mimetypes:'.implode(',', $this->accept)]),
            'max:'.$this->maxSizeKb,
            // The safeguard against an oversized image, unless the image's service provides it (`uploadRules()`,
            // decision 2026-11-24-image-dimension-maximale).
            ...($images && $this->uploadRules === [] ? ['dimensions:max_width=10000,max_height=10000'] : []),
            ...$this->uploadRules,
        ];
    }

    private function assertUpload(): void
    {
        if ($this->type !== FieldType::UPLOAD) {
            throw new LogicException("Champ « {$this->name} » : réservé à un champ de fichiers.");
        }
    }

    /**
     * A list of sub-field groups (decision 2026-11-19-contrat-offer-informations):
     * the user adds a group, fills it in, removes one. The value is the ordered
     * list of groups (`[{title: …, description: …}, …]`); each sub-field is
     * validated for each group. A `hidden` sub-field carries an identifier kept as is.
     */
    public static function repeater(string $name): self
    {
        return new self($name, FieldType::REPEATER);
    }

    /**
     * The sub-fields of a group, in display order.
     *
     * @param  list<Field>  $fields
     */
    public function fields(array $fields): self
    {
        $this->assertRepeater();
        $this->itemFields = array_values($fields);

        return $this;
    }

    /** Maximum number of groups. */
    public function maxItems(int $count): self
    {
        $this->assertRepeater();
        $this->maxItems = max(1, $count);

        return $this;
    }

    /**
     * Label of the add button: for a group (« Ajouter une information ») or a file (« Ajouter un logo »);
     * otherwise, the app's own (decision 2026-11-24-champ-fichier-libelle-ajout).
     */
    public function addLabel(string $label): self
    {
        if ($this->type !== FieldType::UPLOAD) {
            $this->assertRepeater();
        }
        $this->addLabel = $label;

        return $this;
    }

    /**
     * @return list<Field>
     */
    public function getItemFields(): array
    {
        return $this->itemFields;
    }

    private function assertRepeater(): void
    {
        if ($this->type !== FieldType::REPEATER) {
            throw new LogicException("Champ « {$this->name} » : réservé à une liste de groupes.");
        }
    }

    /**
     * Changing the value reloads the form definition with `?<name>=<value>`
     * (decision 2026-11-19-contrat-langue-saisie-allemand): the other fields, including their
     * initial values, may depend on it (e.g. the input language).
     */
    public function reloads(bool $reloads = true): self
    {
        $this->reloads = $reloads;

        return $this;
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

    /**
     * Do not display the label in the app (decision 2026-11-19-nuit-ordre-photos-libelle),
     * for example when the step title already says it; it is still read by
     * accessibility tools and names the field in validation messages.
     */
    public function hideLabel(bool $hidden = true): self
    {
        $this->labelHidden = $hidden;

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

    /**
     * A note displayed before the field label, a warning, like a form's note (decision
     * 2026-11-22-champ-note).
     */
    public function notice(?string $notice): self
    {
        $this->notice = $notice;

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
     * A search box above the dropdown list options, even a short one (a
     * member, a property). Without it: the search only appears beyond 12 options.
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * A list filter without the « Tous » option that the app adds at the top of a dropdown list (decision
     * 2026-11-25-region-sans-tous): a value is picked, « Effacer les filtres » removes it.
     */
    public function withoutAllOption(bool $without = true): self
    {
        $this->withoutAllOption = $without;

        return $this;
    }

    /**
     * A filter specific to the current country (its region, its city, one of its properties): the app resets it when
     * the country changes, the other filters stay (decision 2026-11-25-region-en-tete-recherche).
     */
    public function countryScoped(bool $scoped = true): self
    {
        $this->countryScoped = $scoped;

        return $this;
    }

    /**
     * Dependent list: only the options whose parent equals the value of the field
     * `$field` are offered and accepted.
     */
    public function dependsOn(?string $field): self
    {
        $this->dependsOn = $field;

        return $this;
    }

    /** The parent field of a dependent list, or `null`. */
    public function parentField(): ?string
    {
        return $this->dependsOn;
    }

    /** The same field, without its display condition: its rules, once its visibility is checked separately. */
    public function withoutVisibility(): static
    {
        $field = clone $this;
        $field->visibleWhen = null;

        return $field;
    }

    /**
     * The display condition (`visibleWhen()`), or `null`.
     *
     * @return array{field: string, value: string, values: list<string>}|null
     */
    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhen;
    }

    /**
     * Is the field displayed with these values (by name)? A checkbox is « true » or
     * « false » (decision 2026-11-19-charges-meuble-surface-facultative). A list (chips), as in the app:
     * each of its values must be allowed, an empty list counting as empty (decision 2026-11-26-region-ville-en-puces).
     *
     * @param  array<string, mixed>  $values
     */
    public function isVisibleFor(array $values): bool
    {
        if ($this->visibleWhen === null) {
            return true;
        }
        $current = $values[$this->visibleWhen['field']] ?? null;
        $chosen = is_array($current) ? ($current === [] ? [''] : $current) : [$current];

        return array_diff(array_map(self::conditionValue(...), $chosen), $this->visibleWhen['values']) === [];
    }

    /**
     * A value as a condition compares it: « true »/« false » for a checkbox.
     */
    public static function conditionValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            default => (string) $value,
        };
    }

    /**
     * Only display (and validate) this field if `$field` equals `$value`, or one of the
     * values of the `$value` list (« Logement » for request types only).
     *
     * @param  string|list<string>  $value
     */
    public function visibleWhen(string $field, string|array $value): self
    {
        $values = array_values(array_map('strval', (array) $value));
        $this->visibleWhen = ['field' => $field, 'value' => $values[0] ?? '', 'values' => $values];

        return $this;
    }

    /**
     * The field is used as a list filter (decision 2026-11-20-filtre-option-unique):
     * a single option neither locks it nor becomes its default value,
     * otherwise the app would confuse it with « Tous » and ignore it.
     */
    public function asFilter(): static
    {
        $this->filter = true;

        return $this;
    }

    /**
     * A list (or a phone's country) with only one option: it is
     * selected automatically and cannot change. Unlike `readonly`, the
     * value is sent and validated (decision 2026-11-20-pays-unique-identifiant-double).
     */
    public function isLocked(): bool
    {
        return ! $this->filter
            && in_array($this->type, [FieldType::SELECT, FieldType::PHONE], true)
            && $this->dependsOn === null
            && count($this->resolvedOptions()) === 1;
    }

    /**
     * Initial value sent to the app: the single option of a locked field
     * when nothing else is chosen.
     */
    private function initialValue(): mixed
    {
        if (! $this->isLocked()) {
            return $this->default;
        }

        $only = (string) array_key_first($this->resolvedOptions());

        if ($this->type === FieldType::PHONE) {
            return ['country' => $only, 'number' => is_array($this->default) ? ($this->default['number'] ?? null) : null];
        }

        return $only;
    }

    /**
     * The dates the app's calendar offers (`YYYY-MM-DD`, decision
     * 2026-11-19-bornes-des-dates): a birth date up to today, a
     * booking from today onward. Unbounded: any date. Validation
     * stays in `rules()`.
     */
    public function dateRange(?string $min, ?string $max): self
    {
        if ($this->type !== FieldType::DATE) {
            throw new LogicException("Champ « {$this->name} » : seule une date a des bornes.");
        }

        $this->dateMin = $min;
        $this->dateMax = $max;

        return $this;
    }

    /**
     * The bounds of a rating (1 and 5 by default) and its step (0.1 by default); the app's slider and
     * validation follow them (decisions 2026-11-27-note-bornes-etoiles and 2026-11-27-cle-range).
     */
    public function ratingRange(float $min, float $max, float $step = 0.1): self
    {
        if ($this->type !== FieldType::RATING) {
            throw new LogicException("Champ « {$this->name} » : seule une note a ces bornes.");
        }

        return $this->bounds($min, $max, $step);
    }

    /**
     * A range picked on a two-handle slider, between `$min` and `$max`, by `$step` (decision
     * 2026-11-27-intervalle-curseur); without it, two « Min » and « Max » inputs. The value stays `[min, max]`.
     */
    public function slider(float $min, float $max, float $step = 1): self
    {
        if ($this->type !== FieldType::RANGE) {
            throw new LogicException("Champ « {$this->name} » : seul un intervalle a un curseur.");
        }

        return $this->bounds($min, $max, $step);
    }

    private function bounds(float $min, float $max, float $step): self
    {
        if ($max <= $min) {
            throw new LogicException("Champ « {$this->name} » : la borne maximale doit dépasser la minimale.");
        }
        if ($step <= 0) {
            throw new LogicException("Champ « {$this->name} » : l'incrément doit être positif.");
        }

        $this->boundMin = $min;
        $this->boundMax = $max;
        $this->boundStep = $step;

        return $this;
    }

    /** The stars above a rating's slider (default); `false`: the rating and the slider only. */
    public function stars(bool $shown = true): self
    {
        if ($this->type !== FieldType::RATING) {
            throw new LogicException("Champ « {$this->name} » : seule une note a des étoiles.");
        }

        $this->ratingStars = $shown;

        return $this;
    }

    /**
     * The date is picked on a month grid instead of the native picker
     * (decision 2026-11-19-contrat-calendrier-offre): the `$unavailable` days
     * (`YYYY-MM-DD`) are greyed out. With `$after` (the field of a period's start,
     * « check_in »), they count as nights: the end is picked after the start
     * and at the latest on the first unavailable day following it. Validation stays
     * in `rules()`.
     *
     * @param  list<string>  $unavailable
     */
    public function calendar(array $unavailable, ?string $after = null): self
    {
        if ($this->type !== FieldType::DATE) {
            throw new LogicException("Champ « {$this->name} » : seule une date se choisit sur une grille.");
        }

        $this->calendar = ['unavailable' => array_values($unavailable), 'after' => $after];

        return $this;
    }

    /**
     * Maximum number of characters: validated by the server, sent to the app (which
     * blocks input beyond it and shows a counter).
     */
    public function maxLength(?int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * The numeric bounds and step of a rating or a slider range; `null` without a slider.
     *
     * @return array{min: float, max: float, step: float}|null
     */
    public function getBounds(): ?array
    {
        return $this->boundMin === null || $this->boundMax === null || $this->boundStep === null
            ? null
            : ['min' => $this->boundMin, 'max' => $this->boundMax, 'step' => $this->boundStep];
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
    /**
     * Options computed on first read: a list taken from the database, with no query when the domain loads.
     *
     * @param  Closure(): array<string, string>  $resolver
     */
    public function optionsUsing(Closure $resolver): self
    {
        $this->optionsResolver = $resolver;

        return $this;
    }

    /** A label computed on first read. */
    public function labelUsing(Closure $resolver): self
    {
        $this->labelResolver = $resolver;

        return $this;
    }

    /**
     * The options, those of the deferred computation once resolved.
     *
     * @return array<string, mixed>
     */
    private function resolvedOptions(): array
    {
        if ($this->optionsResolver !== null) {
            $this->options = ($this->optionsResolver)();
            $this->optionsResolver = null;
        }

        return $this->options;
    }

    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Field-specific rules, in addition to those that follow from its type and
     * from `required()` (e.g. `max:255`).
     *
     * @param  list<mixed>  $rules
     */
    /**
     * Published text: rejected if it contains contact details or a contact hint
     * (decision 2026-11-19-contrat-annonces-sans-coordonnees). Short or long text only.
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
     * Complete validation rules of the field, by name (a checkbox group adds
     * the rule for each of its items).
     *
     * @return array<string, list<mixed>>
     */
    public function validationRules(): array
    {
        // Read-only: nothing is expected or taken into account for this field.
        if ($this->readonly) {
            return [];
        }

        if ($this->visibleWhen === null) {
            return $this->ownRules();
        }

        // Hidden by its condition: excluded from validation and from validated data.
        // All allowed values (long term and shared housing), not only the first one.
        $condition = 'exclude_unless:'.$this->visibleWhen['field'].','.implode(',', $this->visibleWhen['values']);

        return array_map(fn (array $rules): array => [$condition, ...$rules], $this->ownRules());
    }

    /**
     * The bounds and step of a slider (rating, range); no rule without a slider.
     *
     * @return list<string>
     */
    private function boundRules(): array
    {
        if ($this->boundMin === null || $this->boundMax === null || $this->boundStep === null) {
            return [];
        }

        return ['between:'.$this->boundMin.','.$this->boundMax, 'multiple_of:'.$this->boundStep];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function ownRules(): array
    {
        $values = array_map('strval', array_keys($this->resolvedOptions()));
        $presence = $this->required ? ['required'] : ['nullable'];

        if ($this->type === FieldType::PHONE) {
            // The number is checked against the chosen country (`<name>_country`, ISO code).
            return [
                $this->name => [...$presence, 'string', 'phone', ...$this->rules],
                $this->name.'_country' => [$this->required ? 'required' : 'required_with:'.$this->name, 'nullable', 'string', Rule::in($values)],
            ];
        }

        if ($this->dependsOn !== null) {
            $parents = collect($this->resolvedOptions())
                ->map(fn (string|array $option): ?string => is_array($option) && isset($option['parent']) ? (string) $option['parent'] : null)
                ->all();

            return [$this->name => [...$presence, ...$this->type->rules($values), new OptionBelongsToParent($this->dependsOn, $parents), ...$this->rules]];
        }

        if (in_array($this->type, [FieldType::CHECKBOX_GROUP, FieldType::MULTISELECT, FieldType::CHIPS], true)) {
            return [
                $this->name => [...$presence, 'array', ...$this->rules],
                $this->name.'.*' => ['string', Rule::in($values)],
            ];
        }

        if ($this->type === FieldType::UPLOAD) {
            $known = array_map('strval', array_keys($this->files));
            $field = $this->name;

            return [
                $this->name => [...$presence, 'array', 'max:'.$this->maxFiles, ...$this->rules],
                // A file already saved, or uploaded for this field by the request's account.
                $this->name.'.*' => ['string', 'distinct', function (string $attribute, mixed $value, Closure $fail) use ($known, $field): void {
                    if (! in_array($value, $known, true) && ! FormUpload::isPendingFor((string) $value, Auth::id(), $field)) {
                        $fail('form::validation.upload_unknown')->translate();
                    }
                }],
            ];
        }

        if ($this->type === FieldType::REPEATER) {
            $rules = [$this->name => [...$presence, 'array', 'max:'.$this->maxItems, ...$this->rules]];

            // Each sub-field, for each group (`informations.*.title`).
            foreach ($this->itemFields as $field) {
                foreach ($field->validationRules() as $key => $fieldRules) {
                    $rules[$this->name.'.*.'.$key] = $fieldRules;
                }
            }

            return $rules;
        }

        if ($this->type === FieldType::RANGE) {
            return [
                // Two bounds, the second at least equal to the first when both are given.
                $this->name => [...$presence, 'array', 'size:2', function (string $attribute, mixed $value, Closure $fail): void {
                    [$min, $max] = array_values((array) $value) + [null, null];
                    if (is_numeric($min) && is_numeric($max) && (float) $max < (float) $min) {
                        $fail('form::validation.range_order')->translate();
                    }
                }],
                // The field's rules (`min:0`) apply to each bound; with a slider, its bounds and step too.
                $this->name.'.*' => ['nullable', 'numeric', ...$this->boundRules(), ...$this->rules],
            ];
        }

        if ($this->type === FieldType::RATING) {
            // The field's bounds, in tenths (decision 2026-11-27-note-bornes-etoiles).
            return [$this->name => [...$presence, 'numeric', ...$this->boundRules(), ...$this->rules]];
        }

        $length = $this->maxLength === null ? [] : ['max:'.$this->maxLength];
        $contact = $this->withoutContactDetails ? [new NoContactDetails] : [];

        return [$this->name => [...$presence, ...$this->type->rules($values), ...$length, ...$contact, ...$this->rules]];
    }

    /**
     * « 50 000 – 100 000 », « ≥ 50 000 », « ≤ 100 000 »; empty: nothing.
     *
     * @param  array<int|string, mixed>  $value
     */
    private static function rangeText(array $value, string $locale): ?string
    {
        [$min, $max] = array_map(
            fn (mixed $bound): ?string => is_numeric($bound) ? (string) Number::format((float) $bound, locale: $locale) : null,
            array_values($value) + [null, null],
        );

        return match (true) {
            $min !== null && $max !== null => $min.' – '.$max,
            $min !== null => '≥ '.$min,
            $max !== null => '≤ '.$max,
            default => null,
        };
    }

    public function getLabel(): ?string
    {
        if ($this->labelResolver !== null) {
            $this->label = ($this->labelResolver)();
            $this->labelResolver = null;
        }

        return $this->label;
    }

    /**
     * Translatable text: stored per language, entered in the account's language in
     * the app, per language tab in the back office. Short or long text only.
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
     * An amount, in a currency the field does not know (the country's or the
     * offer's); `$per`: its time unit (« mois »). Whoever knows the currency
     * adds it with `inCurrency()`: the unit becomes « FCFA / mois » (decision
     * 2026-11-19-contrat-formulaire-etapes-symbole-devise).
     */
    public function money(?string $per = null): self
    {
        $this->money = true;
        $this->moneyPer = $per;

        return $this;
    }

    /**
     * The unit of an amount (`money()`) in the currency with symbol `$symbol`; no
     * effect on another field, or without a symbol. `$withPer` false: the symbol alone,
     * for a display whose label already states the period (« Charges mensuelles »,
     * decision 2026-11-19-charges-sans-par-mois).
     */
    public function inCurrency(?string $symbol, bool $withPer = true): self
    {
        if ($this->money && filled($symbol)) {
            $this->suffix = $this->moneyPer === null || ! $withPer ? $symbol : $symbol.' / '.$this->moneyPer;
        }

        return $this;
    }

    /**
     * Suggestions while typing a text (decision
     * 2026-11-19-contrat-adresse-offre-recherche): the app calls the named route
     * `$route` with `?q=` and offers its answers `{ value, label, fill }`;
     * choosing one fills this field (`value`) and the fields in `fill`.
     */
    public function suggestions(string $route): self
    {
        if ($this->type !== FieldType::TEXT) {
            throw new LogicException("Champ « {$this->name} » : seul un texte propose des suggestions.");
        }

        $this->suggestions = '/'.Str::after(ltrim(route($route, [], false), '/'), 'api/');

        return $this;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    /**
     * Unit displayed after the value (« m² »).
     */
    public function suffix(?string $suffix): self
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Unit that depends on the value of field `$field` (« true », « false » for a
     * checkbox); `suffix()` stays the default unit.
     *
     * @param  array<string, string>  $suffixes  value => unit
     */
    public function suffixWhen(string $field, array $suffixes): self
    {
        $this->suffixWhen = ['field' => $field, 'suffixes' => $suffixes];

        return $this;
    }

    /**
     * The value, human-readable, in the `$locale` language (offer detail
     * page, decision 2026-11-18-fiche-offre): an option's label, « Oui » or
     * « Non », a number or a date in local format, the optional unit; a
     * translatable text in its language. `null`: nothing to display.
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
            FieldType::RANGE => self::rangeText((array) $value, $locale),
            FieldType::CHECKBOX_GROUP, FieldType::MULTISELECT, FieldType::CHIPS => implode(', ', array_map(fn (mixed $item): string => $labels[(string) $item] ?? (string) $item, (array) $value)),
            FieldType::CHECKBOX, FieldType::SWITCH => __('form::field.'.(filter_var($value, FILTER_VALIDATE_BOOL) ? 'yes' : 'no'), [], $locale),
            FieldType::RATING => is_numeric($value) ? str_repeat('★', $stars = max(0, min(5, (int) round((float) $value / ($this->boundMax ?? 5) * 5)))).str_repeat('☆', 5 - $stars).' '.Number::format((float) $value, precision: 1, locale: $locale) : (string) $value,
            FieldType::NUMBER => is_numeric($value) ? (string) Number::format((float) $value, locale: $locale) : (string) $value,
            // The short format of reviews (« 18 nov. 2026 », decision 2026-11-25-date-format-avis).
            FieldType::DATE => Carbon::parse((string) $value)->locale($locale)->isoFormat('ll'),
            default => is_scalar($value) ? (string) $value : null,
        };

        return $text === null || $text === '' ? null : trim($text.' '.($this->suffix ?? ''));
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    /** Its default value; `null`: none. */
    public function defaultValue(): mixed
    {
        return $this->default;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * value => label, for a back office or any display outside the app.
     *
     * @return array<string, string>
     */
    public function optionLabels(): array
    {
        return collect($this->resolvedOptions())
            ->mapWithKeys(fn (string|array $option, string|int $value): array => [(string) $value => is_array($option) ? $option['label'] : $option])
            ->all();
    }

    /**
     * What the app receives to display the field.
     *
     * @return array{name: string, type: string, label: string|null, label_hidden: bool, placeholder: string|null, help: string|null, required: bool, readonly: bool, locked: bool, depends_on: string|null, visible_when: array{field: string, value: string, values: list<string>}|null, reloads: bool, suggestions: string|null, max_length: int|null, suffix: string|null, suffix_when: array{field: string, suffixes: array<string, string>}|null, upload: array{accept: list<string>, max_size_kb: int, max_files: int, add_label: string|null, files: list<array{id: string, url: string}>}|null, repeater: array{fields: list<array<string, mixed>>, max_items: int, add_label: string|null}|null, default: mixed, options: list<array{value: string, label: string, hint: string|null, parent: string|null}>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'label' => $this->getLabel(),
            /** Label not to be displayed (read by accessibility tools only). */
            'label_hidden' => $this->labelHidden,
            'placeholder' => $this->placeholder,
            'help' => $this->help,
            /** A note before the label (a warning); `null`: none. */
            'notice' => $this->notice,
            'required' => $this->required,
            'readonly' => $this->readonly,
            /** Dropdown list: a search box above the options, whatever their number. */
            'searchable' => $this->searchable,
            /** A filter without the « Tous » option; absent: with it (decision 2026-11-25-region-sans-tous). */
            ...($this->withoutAllOption ? ['all_option' => false] : []),
            /** A country-specific filter, reset when the country changes; absent: no. */
            ...($this->countryScoped ? ['country_scoped' => true] : []),
            /** Single option, selected automatically: not editable, but sent. */
            'locked' => $this->isLocked(),
            'depends_on' => $this->dependsOn,
            /** Display only when field `field` equals `value`. */
            'visible_when' => $this->visibleWhen,
            /** Changing the value reloads the form with `?<name>=<value>` (the other fields depend on it). */
            'reloads' => $this->reloads,
            'suggestions' => $this->suggestions,
            'max_length' => $this->maxLength,
            /** Date calendar bounds (`YYYY-MM-DD` or `null`); `null` for another field. */
            /**
             * The field's bounds and its step, a single notion (decision 2026-11-27-cle-range): a date
             * (`YYYY-MM-DD` or `null`, no step), a rating (1 to 5 by 0.1 by default); `null` for another field.
             */
            'range' => match ($this->type) {
                FieldType::DATE => ['min' => $this->dateMin, 'max' => $this->dateMax, 'step' => null],
                FieldType::RATING, FieldType::RANGE => $this->boundMin === null ? null : ['min' => $this->boundMin, 'max' => $this->boundMax, 'step' => $this->boundStep],
                default => null,
            },
            /** A rating: whether its stars are displayed; `null` for another field. */
            'stars' => $this->type === FieldType::RATING ? $this->ratingStars : null,
            // Its date formats, applied by the app (config `form.date_formats`, decision 2026-11-25-dates-formatees-serveur).
            'date_formats' => $this->type !== FieldType::DATE ? null : (array) config('form.date_formats'),
            /** Month grid (date only): unavailable days, and the field of a period's start (`after`); `null`: native picker. */
            'calendar' => $this->calendar,
            /** Unit displayed after the value (« m² »). */
            'suffix' => $this->suffix,
            /** Unit that follows the value of another field (`suffixes`: value => unit); `suffix` otherwise. */
            'suffix_when' => $this->suffixWhen,
            /** Files (`upload`): accepted types, maximum size (KB), maximum count, add button label, already saved files; `null` for another type. */
            'upload' => $this->type !== FieldType::UPLOAD ? null : [
                'accept' => $this->accept,
                'max_size_kb' => $this->maxSizeKb,
                'max_files' => $this->maxFiles,
                'add_label' => $this->addLabel,
                'files' => collect($this->files)->map(fn (string $url, string|int $id): array => ['id' => (string) $id, 'url' => $url])->values()->all(),
            ],
            /** List of groups (`repeater`): sub-fields of a group, maximum count, add button label; `null` for another type. */
            'repeater' => $this->type !== FieldType::REPEATER ? null : [
                'fields' => array_map(fn (Field $field): array => $field->toArray(), $this->itemFields),
                'max_items' => $this->maxItems,
                'add_label' => $this->addLabel,
            ],
            'default' => $this->initialValue(),
            'options' => collect($this->resolvedOptions())
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
