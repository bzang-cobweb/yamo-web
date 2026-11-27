<?php

namespace Bza\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Bza\Form\Enums\FieldType;

/**
 * A form described by the server: the mobile app displays its fields, and the
 * FormRequest of the action receiving it validates with `rules()` — a single source.
 *
 * Each form is declared in the module that owns it and added to the
 * registry (`FormRegistry::register()`).
 */
abstract class Form
{
    public function __construct(protected ?Authenticatable $user = null) {}

    /**
     * Public name of the form, used in the URL (`/api/v1/forms/{name}`).
     */
    abstract public static function name(): string;

    /**
     * The name, from an instance (serialization, OpenAPI contract).
     */
    final public function key(): string
    {
        return static::name();
    }

    /**
     * The fields, in display order. Labels translated into the language
     * of the request.
     *
     * @return list<Field>
     */
    abstract public function fields(): array;

    /**
     * Label of the submit button.
     */
    abstract public function submitLabel(): string;

    /**
     * Submission described to the app (API endpoint, method, follow-up on success); none by
     * default: the app already knows how to submit the form (login, registration…).
     */
    public function submission(): ?Submission
    {
        return null;
    }

    /**
     * The form bound to the record `$key` (`?record=12`): prefilled, and
     * submitted to update it. By default, `record` is ignored. A form that
     * takes it into account responds 404 for a record the account cannot
     * update (decision 2026-11-18-adresses-liste-generique).
     */
    public function forRecord(string $key): static
    {
        return $this;
    }

    /**
     * Title displayed above the form, translated; none by default.
     */
    public function title(): ?string
    {
        return null;
    }

    /**
     * Alignment of the form title and the step titles in the app (decision
     * 2026-11-19-alignement-titre-formulaire): `start` (left, the default) or
     * `center`. The subtitle follows the title.
     *
     * @return 'start'|'center'
     */
    public function titleAlign(): string
    {
        return 'start';
    }

    /**
     * The title of the top bar, translated; `null`: the title (decision 2026-11-22-formulaire-titre-navigation). With it, the title is shown in
     * bold at the top of the content, the subtitle below.
     */
    public function navigationTitle(): ?string
    {
        return null;
    }

    /**
     * Subtitle displayed below the title, translated; none by default.
     */
    public function subtitle(): ?string
    {
        return null;
    }

    /**
     * A description below the subtitle, in grey, translated (the title of an offer, decision
     * 2026-11-22-formulaire-description); none by default.
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * A note below the title and subtitle, set apart, translated: a warning (decision
     * 2026-11-22-formulaire-note); none by default.
     */
    public function notice(): ?string
    {
        return null;
    }

    /**
     * Is the form offered? When unavailable, it responds like an unknown
     * form (404). Available by default.
     */
    public static function isAvailable(): bool
    {
        return true;
    }

    /**
     * Who may request the form definition. By default: a logged-in
     * account. A public form (login) returns `true` for a guest.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    /**
     * Steps of the form, in order. None by default: the form is displayed
     * in one piece. Each field belongs to exactly one step.
     *
     * @return list<Step>
     */
    public function steps(): array
    {
        return [];
    }

    /**
     * Rules for the fields of step `$number` only (starting at 1), or `null` if
     * the step does not exist. Rules linking two fields of the same step
     * (confirmation, "one or the other") apply.
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
     * Validation rules for all fields.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return Arr::collapse(array_map(fn (Field $field): array => $field->validationRules(), $this->fields()));
    }

    /**
     * Field labels, for validation messages.
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
