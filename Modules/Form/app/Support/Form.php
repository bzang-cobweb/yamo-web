<?php

namespace Modules\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Modules\Form\Enums\FieldType;

/**
 * Un formulaire décrit par le serveur : l'app mobile affiche ses champs, et la
 * FormRequest de l'action qui le reçoit valide avec `rules()` — une seule source.
 *
 * Chaque formulaire est déclaré dans le module qui le possède et inscrit au
 * registre (`FormRegistry::register()`).
 */
abstract class Form
{
    public function __construct(protected ?Authenticatable $user = null) {}

    /**
     * Nom public du formulaire, utilisé dans l'URL (`/api/v1/forms/{name}`).
     */
    abstract public static function name(): string;

    /**
     * Le nom, depuis une instance (sérialisation, contrat OpenAPI).
     */
    final public function key(): string
    {
        return static::name();
    }

    /**
     * Les champs, dans l'ordre d'affichage. Libellés traduits dans la langue
     * de la requête.
     *
     * @return list<Field>
     */
    abstract public function fields(): array;

    /**
     * Libellé du bouton d'envoi.
     */
    abstract public function submitLabel(): string;

    /**
     * Envoi décrit à l'app (point d'API, méthode, suite au succès) ; aucun par
     * défaut : l'app sait déjà envoyer le formulaire (connexion, inscription…).
     */
    public function submission(): ?Submission
    {
        return null;
    }

    /**
     * Le formulaire lié à l'enregistrement `$key` (`?record=12`) : prérempli, et
     * envoyé pour le modifier. Par défaut, `record` est ignoré. Un formulaire
     * qui le prend en compte répond 404 pour un enregistrement que le compte ne
     * peut pas modifier (décision 2026-11-18-adresses-liste-generique).
     */
    public function forRecord(string $key): static
    {
        return $this;
    }

    /**
     * Titre affiché au-dessus du formulaire, traduit ; aucun par défaut.
     */
    public function title(): ?string
    {
        return null;
    }

    /**
     * Sous-titre affiché sous le titre, traduit ; aucun par défaut.
     */
    public function subtitle(): ?string
    {
        return null;
    }

    /**
     * Le formulaire est-il proposé ? Indisponible, il répond comme un formulaire
     * inconnu (404). Disponible par défaut.
     */
    public static function isAvailable(): bool
    {
        return true;
    }

    /**
     * Qui peut demander la définition du formulaire. Par défaut : un compte
     * connecté. Un formulaire public (connexion) renvoie `true` pour un invité.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    /**
     * Étapes du formulaire, dans l'ordre. Aucune par défaut : le formulaire
     * s'affiche d'un seul tenant. Chaque champ appartient à une seule étape.
     *
     * @return list<Step>
     */
    public function steps(): array
    {
        return [];
    }

    /**
     * Règles des seuls champs de l'étape `$number` (à partir de 1), ou `null` si
     * l'étape n'existe pas. Les règles qui lient deux champs d'une même étape
     * (confirmation, « l'un ou l'autre ») s'appliquent.
     *
     * @return array<string, list<mixed>>|null
     */
    public function stepRules(int $number): ?array
    {
        $step = $this->steps()[$number - 1] ?? null;

        if ($step === null) {
            return null;
        }

        $fields = array_filter($this->fields(), fn (Field $field): bool => in_array($field->name, $step->fields, true));

        return Arr::collapse(array_map(fn (Field $field): array => $field->validationRules(), array_values($fields)));
    }

    /**
     * Règles de validation de tous les champs.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return Arr::collapse(array_map(fn (Field $field): array => $field->validationRules(), $this->fields()));
    }

    /**
     * Libellés des champs, pour les messages de validation.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect($this->fields())
            ->filter(fn (Field $field): bool => $field->getLabel() !== null)
            ->flatMap(fn (Field $field): array => $field->type === FieldType::PHONE
                ? [$field->name => $field->getLabel(), $field->name.'_country' => $field->getLabel()]
                : [$field->name => $field->getLabel()])
            ->all();
    }
}
