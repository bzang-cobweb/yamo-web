<?php

namespace Modules\Auth\Forms;

use Collator;
use Illuminate\Support\Facades\App;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
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
            // Facultative : `required` à faux, la règle devient `nullable`.
            Field::date('birth_date')
                ->label(__('auth::user.birth_date'))
                ->default($user?->birth_date?->format('Y-m-d'))
                ->rules(['after_or_equal:'.self::BIRTH_DATE_MIN, 'before_or_equal:today']),
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
        $collator = new Collator(App::getLocale());

        return Country::query()->active()->get()
            ->mapWithKeys(fn (Country $country): array => [(string) $country->getKey() => $country->label()])
            ->sort(fn (string $a, string $b): int => (int) $collator->compare($a, $b))
            ->all();
    }

    /**
     * Régions actives des pays actifs, chacune avec son pays pour parent.
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

    public function submitLabel(): string
    {
        return __('auth::form.save');
    }
}
