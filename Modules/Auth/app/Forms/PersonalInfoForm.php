<?php

namespace Modules\Auth\Forms;

use App\Models\Setting;
use Collator;
use Illuminate\Support\Facades\App;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Rules\MinimumAge;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

/**
 * Informations personnelles, depuis le profil de l'app mobile
 * (`PATCH /api/v1/auth/me/personal-info`) : prénom, nom, genre (obligatoire),
 * date de naissance, pays et région du pays, description (facultatifs).
 */
class PersonalInfoForm extends Form
{
    /** Date de naissance la plus ancienne acceptée (aussi par le back-office). */
    public const BIRTH_DATE_MIN = '1900-01-01';

    /**
     * La date de naissance, ici comme à l'inscription. Facultative, sauf si un âge
     * minimum est réglé (décision 2026-11-19-age-minimum) : alors obligatoire, et elle
     * doit donner au moins cet âge, rappelé sous le champ.
     */
    public static function birthDateField(): Field
    {
        $minimumAge = Setting::minimumAge();
        $rules = ['after_or_equal:'.self::BIRTH_DATE_MIN, 'before_or_equal:today'];

        if ($minimumAge !== null) {
            $rules[] = new MinimumAge($minimumAge);
        }

        // `required` à faux, la règle devient `nullable`. Le calendrier s'arrête à aujourd'hui,
        // ou à la dernière date qui donne l'âge minimum (décision 2026-11-19-bornes-des-dates).
        return Field::date('birth_date')
            ->label(__('auth::user.birth_date'))
            ->dateRange(self::BIRTH_DATE_MIN, today()->subYears($minimumAge ?? 0)->toDateString())
            ->help($minimumAge === null ? null : __('auth::form.birth_date.help', ['age' => $minimumAge]))
            ->required($minimumAge !== null)
            ->rules($rules);
    }

    public static function name(): string
    {
        return 'auth.profile.personal';
    }

    public function fields(): array
    {
        $user = $this->user instanceof User ? $this->user : null;

        return [
            Field::text('first_name')
                ->label(__('auth::user.first_name'))
                ->default($user?->first_name)
                ->required()
                ->rules(['max:255']),
            Field::text('last_name')
                ->label(__('auth::user.last_name'))
                ->default($user?->last_name)
                ->required()
                ->rules(['max:255']),
            Field::select('gender')
                ->label(__('auth::user.gender'))
                ->options(GenderEnum::options())
                ->default($user?->gender?->value)
                ->required(),
            self::birthDateField()->default($user?->birth_date?->format('Y-m-d')),
            Field::select('country_id')
                ->label(__('auth::user.country'))
                ->options(self::countryOptions())
                ->default($user?->country_id === null ? null : (string) $user->country_id),
            // Région : seulement celles du pays choisi (liste dépendante).
            Field::select('region_id')
                ->label(__('auth::user.region'))
                ->dependsOn('country_id')
                ->options(self::regionOptions())
                ->default($user?->region_id === null ? null : (string) $user->region_id),
            Field::textarea('description')
                ->label(__('auth::user.description'))
                ->default($user?->description),
        ];
    }

    /**
     * Pays actifs, dans la langue de la requête (nom natif en complément).
     *
     * @return array<string, string>
     */
    public static function countryOptions(): array
    {
        return Country::options();
    }

    /**
     * Régions actives des pays actifs, chacune avec son pays pour parent, par
     * ordre alphabétique : l'ordre du back-office (`position`) ne s'applique pas
     * ici (décision 2026-11-17-regions-alphabetiques-menu-en-code).
     *
     * @return array<string, array{label: string, parent: string}>
     */
    public static function regionOptions(): array
    {
        // Tri selon la langue : « Bâle » avant « Berne ».
        $collator = new Collator(App::getLocale());

        return Region::query()->active()->whereHas('country', fn ($query) => $query->active())->get()
            ->sort(fn (Region $a, Region $b): int => (int) $collator->compare((string) $a->name, (string) $b->name))
            ->mapWithKeys(fn (Region $region): array => [
                (string) $region->getKey() => ['label' => (string) $region->name, 'parent' => (string) $region->country_id],
            ])
            ->all();
    }

    public function title(): string
    {
        return __('auth::form.personal.title');
    }

    /**
     * L'app envoie le formulaire à `api.v1.auth.me.personal-info.update`
     * (décision 2026-11-17-formulaires-profil-generiques).
     */
    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.personal-info.update', SuccessAction::PROFILE, __('auth::mobile.profile.saved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
