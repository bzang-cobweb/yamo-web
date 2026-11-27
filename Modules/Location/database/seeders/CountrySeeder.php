<?php

namespace Modules\Location\Database\Seeders;

use Bza\Form\Support\Locales;
use Illuminate\Database\Seeder;
use libphonenumber\PhoneNumberUtil;
use Locale;
use Modules\Location\Models\Country;
use Modules\Location\Support\Currencies;
use Modules\Location\Support\RegionData;
use Modules\Location\Support\Timezones;
use ResourceBundle;

/**
 * Tous les pays connus de libphonenumber (environ 245), avec leur indicatif et leurs
 * noms fr, en et natif tirés de l'extension intl (ICU).
 *
 * Rejouable : un pays existant voit ses noms mis à jour, mais garde son `active` et
 * son `dial_code`, sa `currency`, son `timezone` et son `region_label`, modifiables dans le back-office.
 */
class CountrySeeder extends Seeder
{
    /**
     * Langue du nom natif imposée, quand la règle automatique (langue officielle la
     * plus parlée selon ICU) ne donne pas le nom attendu.
     *
     * @var array<string, string>
     */
    private const NATIVE_LANGUAGES = [
        'LU' => 'lb', // trilingue : le luxembourgeois est la langue nationale (ICU donne fr).
    ];

    /** Statuts ICU retenus, par ordre de préférence. */
    private const OFFICIAL_STATUSES = ['official', 'de_facto_official'];

    /** @var array<string, true>|null */
    private ?array $icuLocales = null;

    private ?ResourceBundle $territories = null;

    public function run(): void
    {
        $phoneNumbers = PhoneNumberUtil::getInstance();

        foreach ($phoneNumbers->getSupportedRegions() as $code) {
            // Un nom par langue de l'app (décision 2026-11-19-contrat-langue-saisie-allemand).
            $names = collect(Locales::values())->mapWithKeys(fn (string $locale): array => [$locale => $this->regionName($code, $locale) ?? $code])->all();

            $country = Country::query()->firstOrNew(['code' => $code]);
            $country->setTranslations('name', $names);
            $country->native_name = $this->nativeName($code) ?? $names['en'] ?? $names[Locales::default()];

            if (! $country->exists) {
                $country->dial_code = '+'.$phoneNumbers->getCountryCodeForRegion($code);
                $country->active = true;
            }

            // Devise d'après ICU, si elle n'a pas encore été posée (modifiable ensuite).
            $country->currency ??= Currencies::forCountry($code);

            // Fuseau horaire d'après PHP, s'il n'a pas encore été posé (modifiable ensuite ; décision
            // 2026-11-25-fuseau-par-pays).
            $country->timezone ??= Timezones::forCountry($code);

            // Le nom de ses régions (« Canton », « District »…), traduit, d'après son fichier de régions s'il en a un, s'il
            // n'a pas encore été posé (modifiable ensuite au back-office ; décision 2026-11-25-regions-par-pays).
            $label = RegionData::of($code)['label'] ?? null;
            if ($label !== null && array_filter($country->getTranslations('region_label')) === []) {
                $country->setTranslations('region_label', $label);
            }

            $country->save();
        }
    }

    /**
     * Nom du pays dans la langue de ses habitants, ou null si ICU ne le connaît pas.
     */
    public function nativeName(string $code): ?string
    {
        $this->icuLocales ??= array_fill_keys(ResourceBundle::getLocales(''), true);

        foreach ($this->nativeLanguageCandidates($code) as $language) {
            // Une langue sans données ICU retomberait sur la langue par défaut.
            if (! isset($this->icuLocales[$language])) {
                continue;
            }

            $name = $this->regionName($code, $language);

            if ($name !== null) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Langues candidates du nom natif : l'exception éventuelle, puis les langues
     * officielles du pays selon ICU (supplementalData/territoryInfo), de la plus
     * parlée à la moins parlée.
     *
     * @return list<string>
     */
    private function nativeLanguageCandidates(string $code): array
    {
        $candidates = isset(self::NATIVE_LANGUAGES[$code]) ? [self::NATIVE_LANGUAGES[$code]] : [];
        $this->territories ??= ResourceBundle::create('supplementalData', null, false)?->get('territoryInfo');
        $territory = $this->territories?->get($code);

        if (! $territory instanceof ResourceBundle) {
            return $candidates;
        }

        foreach (self::OFFICIAL_STATUSES as $status) {
            $shares = [];

            foreach ($territory as $language => $info) {
                if ($info instanceof ResourceBundle && $info->get('officialStatus') === $status) {
                    // `populationShareF` : part de la population, en flottant encodé
                    // par ICU dont l'ordre suit celui des valeurs.
                    $shares[(string) $language] = (int) $info->get('populationShareF');
                }
            }

            arsort($shares);
            array_push($candidates, ...array_keys($shares));
        }

        return $candidates;
    }

    public function regionName(string $code, string $locale): ?string
    {
        $name = Locale::getDisplayRegion('-'.$code, $locale);

        // ICU renvoie le code lui-même quand la langue n'a pas de nom pour ce pays.
        return $name === false || $name === '' || $name === $code ? null : $name;
    }
}
