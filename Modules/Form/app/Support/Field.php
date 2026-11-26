<?php

namespace Modules\Form\Support;

use App\Support\Locales;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LogicException;
use Modules\Form\Enums\FieldType;
use Modules\Form\Models\FormUpload;
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

    /** Une note avant le libellé : un avertissement (décision 2026-11-22-champ-note). */
    private ?string $notice = null;

    private bool $required = false;

    /** Libellé non affiché par l'app (`hideLabel()`) ; lu par l'accessibilité, il sert aux messages. */
    private bool $labelHidden = false;

    /** Affiché, non modifiable, jamais validé ni enregistré. */
    private bool $readonly = false;

    /** Liste déroulante : une recherche en tête des options, quel que soit leur nombre. */
    private bool $searchable = false;

    /** Un filtre de liste sans l'option « Tous » (décision 2026-11-25-region-sans-tous). */
    private bool $withoutAllOption = false;

    /** Un filtre propre au pays (région, ville…), remis à zéro quand on change de pays (décision 2026-11-25-region-en-tete-recherche). */
    private bool $countryScoped = false;

    /** Liste dépendante : nom du champ parent (ex. le pays d'une région). */
    private ?string $dependsOn = null;

    /** Un filtre : jamais verrouillé, « Tous » reste possible (décision 2026-11-20-filtre-option-unique). */
    private bool $filter = false;

    private mixed $default = null;

    /**
     * Affiché seulement quand un autre champ a une valeur donnée ; masqué, il
     * n'est ni validé ni pris en compte. `values` : les valeurs qui l'affichent
     * (`value`, la première, gardée pour les clients qui ne lisent qu'une valeur).
     *
     * @var array{field: string, value: string, values: list<string>}|null
     */
    private ?array $visibleWhen = null;

    /** Texte sur plusieurs lignes : nombre maximal de caractères. */
    public const TEXTAREA_MAX_LENGTH = 1000;

    private ?int $maxLength = null;

    /** Bornes d'une date (`AAAA-MM-JJ`), proposées par le calendrier de l'app ; `null` : sans borne. */
    private ?string $dateMin = null;

    private ?string $dateMax = null;

    /** @var array{unavailable: list<string>, after: string|null}|null */
    private ?array $calendar = null;

    /** Unité affichée après la valeur (« m² ») : formulaire et fiche (décision 2026-11-18-fiche-offre). */
    private ?string $suffix = null;

    /**
     * Unité qui suit la valeur d'un autre champ (décision 2026-11-18-unite-prix-chambres) :
     * `{field: 'rental_type', suffixes: {'furnished': 'FCFA / jour', 'long_term': 'FCFA / mois'}}`.
     *
     * @var array{field: string, suffixes: array<string, string>}|null
     */
    private ?array $suffixWhen = null;

    /** Texte rangé par langue (décision 2026-11-18-offres-multilingues-categories-arbre). */
    private bool $translatable = false;

    /** Montant dans une devise (`money()`) : son unité de temps éventuelle (« mois »). */
    private bool $money = false;

    private ?string $moneyPer = null;

    /** Suggestions pendant la saisie : point d'API relatif (`/v1/…`), ou aucune. */
    private ?string $suggestions = null;

    /**
     * Fichiers (`upload()`) : types MIME acceptés, taille maximale d'un fichier (Ko),
     * nombre maximal de fichiers, et fichiers déjà enregistrés (identifiant => adresse).
     *
     * @var list<string>
     */
    private array $accept = [];

    private int $maxSizeKb = 10240;

    private int $maxFiles = 1;

    /**
     * Règles de plus pour chaque fichier envoyé (`uploadRules()`) : le minimum d'une image.
     *
     * @var list<mixed>
     */
    private array $uploadRules = [];

    /** @var array<string, string> */
    private array $files = [];

    /**
     * Liste de groupes (`repeater()`) : les sous-champs d'un groupe, le nombre
     * maximal de groupes et le libellé du bouton d'ajout.
     *
     * @var list<Field>
     */
    private array $itemFields = [];

    private int $maxItems = 10;

    private ?string $addLabel = null;

    /** Changer la valeur recharge le formulaire avec `?<nom>=<valeur>` (`reloads()`). */
    private bool $reloads = false;

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

    /** Les options, calculées à la première lecture (décision 2026-11-25-cases-par-categories) ; `null` : `$options`. */
    private ?Closure $optionsResolver = null;

    /** Le libellé, calculé à la première lecture ; `null` : `$label`. */
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

    /**
     * Liste déroulante à choix multiples : la valeur est la liste des options
     * choisies (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
     */
    public static function multiselect(string $name): self
    {
        return new self($name, FieldType::MULTISELECT);
    }

    /**
     * Choix multiples en puces sur une ligne qui défile : la valeur est la liste des options choisies, comme
     * `multiselect` (décision 2026-11-26-filtres-en-puces).
     */
    public static function chips(string $name): self
    {
        return new self($name, FieldType::CHIPS);
    }

    /**
     * Intervalle : deux bornes `[min, max]`, chacune facultative ; max ≥ min.
     * L'unité éventuelle (`suffix()`) vaut pour les deux.
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

    /**
     * Fichiers (décision 2026-11-19-contrat-champ-upload-photos) : l'app envoie
     * chaque fichier dès qu'il est choisi (`POST /api/v1/forms/{form}/uploads/{champ}`),
     * validé par `fileRules()`, et reçoit son identifiant ; la valeur du champ est la
     * liste ordonnée des identifiants, fichiers déjà enregistrés (`files()`) compris.
     * Types acceptés, taille et nombre maximal sont déclarés ici et servis à l'app.
     */
    public static function upload(string $name): self
    {
        return new self($name, FieldType::UPLOAD);
    }

    /**
     * Types MIME acceptés (`image/jpeg`…).
     *
     * @param  list<string>  $mimeTypes
     */
    public function accept(array $mimeTypes): self
    {
        $this->assertUpload();
        $this->accept = array_values($mimeTypes);

        return $this;
    }

    /** Taille maximale d'un fichier, en Ko. */
    public function maxSize(int $kilobytes): self
    {
        $this->assertUpload();
        $this->maxSizeKb = $kilobytes;

        return $this;
    }

    /**
     * Des règles de plus pour chaque fichier envoyé, après le type et le poids : le minimum d'une image, celui de son
     * service (`ConfiguredImageService::uploadRules()`, décision 2026-11-24-image-regle-minimum-par-cadre).
     *
     * @param  list<mixed>  $rules
     */
    public function uploadRules(array $rules): self
    {
        $this->assertUpload();
        $this->uploadRules = $rules;

        return $this;
    }

    /** Nombre maximal de fichiers (1 : un seul). */
    public function maxFiles(int $count): self
    {
        $this->assertUpload();
        $this->maxFiles = max(1, $count);

        return $this;
    }

    /**
     * Les fichiers déjà enregistrés, dans l'ordre : identifiant => adresse. Ils sont
     * la valeur initiale du champ, et leurs identifiants sont acceptés à l'envoi.
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
     * Règles d'un fichier envoyé pour ce champ : type, taille ; une image démesurée
     * à décoder est refusée.
     *
     * @return list<mixed>
     */
    public function fileRules(): array
    {
        $this->assertUpload();
        $images = $this->accept !== [] && array_filter($this->accept, fn (string $type): bool => ! str_starts_with($type, 'image/')) === [];

        return [
            // Un seul message par refus : le premier qui s'applique.
            'bail',
            'required',
            'file',
            ...($this->accept === [] ? [] : ['mimetypes:'.implode(',', $this->accept)]),
            'max:'.$this->maxSizeKb,
            // Le garde-fou contre une image démesurée, sauf quand le service de l'image le donne (`uploadRules()`,
            // décision 2026-11-24-image-dimension-maximale).
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
     * Une liste de groupes de sous-champs (décision 2026-11-19-contrat-offer-informations) :
     * l'utilisateur ajoute un groupe, le remplit, en retire un. La valeur est la liste
     * ordonnée des groupes (`[{title: …, description: …}, …]`) ; chaque sous-champ est
     * validé pour chaque groupe. Un sous-champ `hidden` porte un identifiant gardé tel quel.
     */
    public static function repeater(string $name): self
    {
        return new self($name, FieldType::REPEATER);
    }

    /**
     * Les sous-champs d'un groupe, dans l'ordre d'affichage.
     *
     * @param  list<Field>  $fields
     */
    public function fields(array $fields): self
    {
        $this->assertRepeater();
        $this->itemFields = array_values($fields);

        return $this;
    }

    /** Nombre maximal de groupes. */
    public function maxItems(int $count): self
    {
        $this->assertRepeater();
        $this->maxItems = max(1, $count);

        return $this;
    }

    /**
     * Libellé du bouton d'ajout : d'un groupe (« Ajouter une information »), ou d'un fichier (« Ajouter un logo ») ;
     * à défaut, celui de l'app (décision 2026-11-24-champ-fichier-libelle-ajout).
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
     * Changer la valeur recharge la définition du formulaire avec `?<nom>=<valeur>`
     * (décision 2026-11-19-contrat-langue-saisie-allemand) : les autres champs, leurs
     * valeurs de départ comprises, peuvent en dépendre (ex. la langue de saisie).
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
     * Ne pas afficher le libellé dans l'app (décision 2026-11-19-nuit-ordre-photos-libelle),
     * par exemple quand le titre de l'étape le dit déjà ; il reste lu par
     * l'accessibilité et nomme le champ dans les messages de validation.
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
     * Une note à afficher avant le libellé du champ, un avertissement, comme la note d'un formulaire (décision
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
     * Une recherche en tête des options de la liste déroulante, même courte (un
     * membre, un logement). Sans : la recherche n'apparaît qu'au-delà de 12 options.
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Un filtre de liste sans l'option « Tous » que l'app ajoute en tête d'une liste déroulante (décision
     * 2026-11-25-region-sans-tous) : on choisit une valeur, « Effacer les filtres » la retire.
     */
    public function withoutAllOption(bool $without = true): self
    {
        $this->withoutAllOption = $without;

        return $this;
    }

    /**
     * Un filtre propre au pays courant (sa région, sa ville, un de ses logements) : l'app le remet à zéro quand on
     * change de pays, les autres filtres restent (décision 2026-11-25-region-en-tete-recherche).
     */
    public function countryScoped(bool $scoped = true): self
    {
        $this->countryScoped = $scoped;

        return $this;
    }

    /**
     * Liste dépendante : seules les options dont le parent vaut la valeur du champ
     * `$field` sont proposées et acceptées.
     */
    public function dependsOn(?string $field): self
    {
        $this->dependsOn = $field;

        return $this;
    }

    /** Le champ parent d'une liste dépendante, ou `null`. */
    public function parentField(): ?string
    {
        return $this->dependsOn;
    }

    /**
     * La condition d'affichage (`visibleWhen()`), ou `null`.
     *
     * @return array{field: string, value: string, values: list<string>}|null
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

        return in_array(self::conditionValue($values[$this->visibleWhen['field']] ?? null), $this->visibleWhen['values'], true);
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

    /**
     * N'afficher (et ne valider) ce champ que si `$field` vaut `$value`, ou l'une des
     * valeurs de la liste `$value` (« Logement » pour les seuls types de demande).
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
     * Le champ sert de filtre d'une liste (décision 2026-11-20-filtre-option-unique) :
     * une seule option ne le verrouille pas et n'en fait pas la valeur par défaut,
     * sinon l'app la confondrait avec « Tous » et l'ignorerait.
     */
    public function asFilter(): static
    {
        $this->filter = true;

        return $this;
    }

    /**
     * Une liste (ou le pays d'un téléphone) qui n'a qu'une option : elle est
     * choisie d'office et ne peut pas changer. Contrairement à `readonly`, la
     * valeur est envoyée et validée (décision 2026-11-20-pays-unique-identifiant-double).
     */
    public function isLocked(): bool
    {
        return ! $this->filter
            && in_array($this->type, [FieldType::SELECT, FieldType::PHONE], true)
            && $this->dependsOn === null
            && count($this->resolvedOptions()) === 1;
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

        $only = (string) array_key_first($this->resolvedOptions());

        if ($this->type === FieldType::PHONE) {
            return ['country' => $only, 'number' => is_array($this->default) ? ($this->default['number'] ?? null) : null];
        }

        return $only;
    }

    /**
     * Les dates que le calendrier de l'app propose (`AAAA-MM-JJ`, décision
     * 2026-11-19-bornes-des-dates) : une naissance jusqu'à aujourd'hui, une
     * réservation à partir d'aujourd'hui. Sans borne, toute date. La validation
     * reste dans `rules()`.
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
     * La date se choisit sur une grille de mois au lieu du sélecteur natif
     * (décision 2026-11-19-contrat-calendrier-offre) : les jours `$unavailable`
     * (`AAAA-MM-JJ`) y sont grisés. Avec `$after` (le champ du début d'une période,
     * « check_in »), ils comptent comme des nuits : la fin se choisit après le début
     * et au plus tard le premier jour indisponible qui le suit. La validation reste
     * dans `rules()`.
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
    /**
     * Des options calculées à la première lecture : une liste tirée de la base, sans requête au chargement du domaine.
     *
     * @param  Closure(): array<string, string>  $resolver
     */
    public function optionsUsing(Closure $resolver): self
    {
        $this->optionsResolver = $resolver;

        return $this;
    }

    /** Un libellé calculé à la première lecture. */
    public function labelUsing(Closure $resolver): self
    {
        $this->labelResolver = $resolver;

        return $this;
    }

    /**
     * Les options, celles du calcul différé une fois résolues.
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
        // Toutes les valeurs admises (longue durée et colocation), pas seulement la première.
        $condition = 'exclude_unless:'.$this->visibleWhen['field'].','.implode(',', $this->visibleWhen['values']);

        return array_map(fn (array $rules): array => [$condition, ...$rules], $this->ownRules());
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function ownRules(): array
    {
        $values = array_map('strval', array_keys($this->resolvedOptions()));
        $presence = $this->required ? ['required'] : ['nullable'];

        if ($this->type === FieldType::PHONE) {
            // Le numéro est vérifié selon le pays choisi (`<nom>_country`, code ISO).
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
                // Un fichier déjà enregistré, ou envoyé pour ce champ par le compte de la requête.
                $this->name.'.*' => ['string', 'distinct', function (string $attribute, mixed $value, Closure $fail) use ($known, $field): void {
                    if (! in_array($value, $known, true) && ! FormUpload::isPendingFor((string) $value, Auth::id(), $field)) {
                        $fail('form::validation.upload_unknown')->translate();
                    }
                }],
            ];
        }

        if ($this->type === FieldType::REPEATER) {
            $rules = [$this->name => [...$presence, 'array', 'max:'.$this->maxItems, ...$this->rules]];

            // Chaque sous-champ, pour chaque groupe (`informations.*.title`).
            foreach ($this->itemFields as $field) {
                foreach ($field->validationRules() as $key => $fieldRules) {
                    $rules[$this->name.'.*.'.$key] = $fieldRules;
                }
            }

            return $rules;
        }

        if ($this->type === FieldType::RANGE) {
            return [
                // Deux bornes, la seconde au moins égale à la première quand les deux sont données.
                $this->name => [...$presence, 'array', 'size:2', function (string $attribute, mixed $value, Closure $fail): void {
                    [$min, $max] = array_values((array) $value) + [null, null];
                    if (is_numeric($min) && is_numeric($max) && (float) $max < (float) $min) {
                        $fail('form::validation.range_order')->translate();
                    }
                }],
                // Les règles du champ (`min:0`) valent pour chaque borne.
                $this->name.'.*' => ['nullable', 'numeric', ...$this->rules],
            ];
        }

        $length = $this->maxLength === null ? [] : ['max:'.$this->maxLength];
        $contact = $this->withoutContactDetails ? [new NoContactDetails] : [];

        return [$this->name => [...$presence, ...$this->type->rules($values), ...$length, ...$contact, ...$this->rules]];
    }

    /**
     * « 50 000 – 100 000 », « ≥ 50 000 », « ≤ 100 000 » ; vide : rien.
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
     * effet sur un autre champ, ou sans symbole. `$withPer` à faux : le symbole seul,
     * pour un affichage dont le libellé dit déjà la période (« Charges mensuelles »,
     * décision 2026-11-19-charges-sans-par-mois).
     */
    public function inCurrency(?string $symbol, bool $withPer = true): self
    {
        if ($this->money && filled($symbol)) {
            $this->suffix = $this->moneyPer === null || ! $withPer ? $symbol : $symbol.' / '.$this->moneyPer;
        }

        return $this;
    }

    /**
     * Suggestions pendant la saisie d'un texte (décision
     * 2026-11-19-contrat-adresse-offre-recherche) : l'app appelle la route nommée
     * `$route` avec `?q=` et propose ses réponses `{ value, label, fill }` ; en
     * choisir une remplit ce champ (`value`) et les champs de `fill`.
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
            FieldType::RANGE => self::rangeText((array) $value, $locale),
            FieldType::CHECKBOX_GROUP, FieldType::MULTISELECT, FieldType::CHIPS => implode(', ', array_map(fn (mixed $item): string => $labels[(string) $item] ?? (string) $item, (array) $value)),
            FieldType::CHECKBOX, FieldType::SWITCH => __('form::field.'.(filter_var($value, FILTER_VALIDATE_BOOL) ? 'yes' : 'no'), [], $locale),
            FieldType::RATING => is_numeric($value) ? str_repeat('★', $stars = max(0, min(5, (int) $value))).str_repeat('☆', 5 - $stars) : (string) $value,
            FieldType::NUMBER => is_numeric($value) ? (string) Number::format((float) $value, locale: $locale) : (string) $value,
            // Le format court des avis (« 18 nov. 2026 », décision 2026-11-25-date-format-avis).
            FieldType::DATE => Carbon::parse((string) $value)->locale($locale)->isoFormat('ll'),
            default => is_scalar($value) ? (string) $value : null,
        };

        return $text === null || $text === '' ? null : trim($text.' '.($this->suffix ?? ''));
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    /** Sa valeur par défaut ; `null` : aucune. */
    public function defaultValue(): mixed
    {
        return $this->default;
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
        return collect($this->resolvedOptions())
            ->mapWithKeys(fn (string|array $option, string|int $value): array => [(string) $value => is_array($option) ? $option['label'] : $option])
            ->all();
    }

    /**
     * Ce que l'app reçoit pour afficher le champ.
     *
     * @return array{name: string, type: string, label: string|null, label_hidden: bool, placeholder: string|null, help: string|null, required: bool, readonly: bool, locked: bool, depends_on: string|null, visible_when: array{field: string, value: string, values: list<string>}|null, reloads: bool, suggestions: string|null, max_length: int|null, suffix: string|null, suffix_when: array{field: string, suffixes: array<string, string>}|null, upload: array{accept: list<string>, max_size_kb: int, max_files: int, add_label: string|null, files: list<array{id: string, url: string}>}|null, repeater: array{fields: list<array<string, mixed>>, max_items: int, add_label: string|null}|null, default: mixed, options: list<array{value: string, label: string, hint: string|null, parent: string|null}>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'label' => $this->getLabel(),
            /** Libellé à ne pas afficher (lu par l'accessibilité seulement). */
            'label_hidden' => $this->labelHidden,
            'placeholder' => $this->placeholder,
            'help' => $this->help,
            /** Une note avant le libellé (un avertissement) ; `null` : aucune. */
            'notice' => $this->notice,
            'required' => $this->required,
            'readonly' => $this->readonly,
            /** Liste déroulante : une recherche en tête des options, quel que soit leur nombre. */
            'searchable' => $this->searchable,
            /** Un filtre sans l'option « Tous » ; absent : avec (décision 2026-11-25-region-sans-tous). */
            ...($this->withoutAllOption ? ['all_option' => false] : []),
            /** Un filtre propre au pays, remis à zéro quand on en change ; absent : non. */
            ...($this->countryScoped ? ['country_scoped' => true] : []),
            /** Option unique, choisie d'office : non modifiable, mais envoyée. */
            'locked' => $this->isLocked(),
            'depends_on' => $this->dependsOn,
            /** Afficher seulement quand le champ `field` vaut `value`. */
            'visible_when' => $this->visibleWhen,
            /** Changer la valeur recharge le formulaire avec `?<nom>=<valeur>` (les autres champs en dépendent). */
            'reloads' => $this->reloads,
            'suggestions' => $this->suggestions,
            'max_length' => $this->maxLength,
            /** Bornes du calendrier d'une date (`AAAA-MM-JJ` ou `null`) ; `null` pour un autre champ. */
            'date_range' => $this->type !== FieldType::DATE ? null : ['min' => $this->dateMin, 'max' => $this->dateMax],
            // Les formats de ses dates, appliqués par l'app (config `form.date_formats`, décision 2026-11-25-dates-formatees-serveur).
            'date_formats' => $this->type !== FieldType::DATE ? null : (array) config('form.date_formats'),
            /** Grille de mois (date seulement) : jours indisponibles, et le champ du début d'une période (`after`) ; `null` : sélecteur natif. */
            'calendar' => $this->calendar,
            /** Unité affichée après la valeur (« m² »). */
            'suffix' => $this->suffix,
            /** Unité qui suit la valeur d'un autre champ (`suffixes` : valeur => unité) ; `suffix` à défaut. */
            'suffix_when' => $this->suffixWhen,
            /** Fichiers (`upload`) : types acceptés, taille maximale (Ko), nombre maximal, libellé du bouton d'ajout, fichiers déjà enregistrés ; `null` pour un autre type. */
            'upload' => $this->type !== FieldType::UPLOAD ? null : [
                'accept' => $this->accept,
                'max_size_kb' => $this->maxSizeKb,
                'max_files' => $this->maxFiles,
                'add_label' => $this->addLabel,
                'files' => collect($this->files)->map(fn (string $url, string|int $id): array => ['id' => (string) $id, 'url' => $url])->values()->all(),
            ],
            /** Liste de groupes (`repeater`) : sous-champs d'un groupe, nombre maximal, libellé du bouton d'ajout ; `null` pour un autre type. */
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
