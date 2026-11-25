<?php

namespace Modules\Location\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Modules\Location\Support\RegionData;

/**
 * Les régions de chaque pays qui a un fichier de données (`RegionData` : Cameroun, Côte d'Ivoire, Suisse…), rattachées
 * à leur pays (CountrySeeder d'abord) (décision 2026-11-25-regions-par-pays).
 *
 * Rejouable : une région déjà présente dans le pays (même nom dans une des langues, sans casse ni espaces en trop) voit
 * ses noms mis à jour et garde son `active` et sa `position`, réglés au back-office ; une nouvelle se place à la fin de
 * son pays. Aucune région n'est supprimée : ses adresses le seraient avec elle.
 */
class RegionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RegionData::countries() as $code) {
            $country = Country::query()->where('code', $code)->first();
            if ($country === null) {
                continue;
            }

            $existing = $country->regions()->get();
            foreach (RegionData::of($code)['regions'] ?? [] as $names) {
                $region = $existing->first(fn (Region $region): bool => self::sameName($region, $names))
                    ?? new Region(['country_id' => $country->getKey(), 'active' => true]);

                $region->setTranslations('name', $names);
                $region->save();
            }
        }
    }

    /**
     * La région porte-t-elle un de ces noms, dans une de ses langues ?
     *
     * @param  array<string, string>  $names
     */
    private static function sameName(Region $region, array $names): bool
    {
        $plain = fn (string $name): string => Str::lower(Str::squish($name));
        $wanted = array_map($plain, array_values($names));

        foreach ($region->getTranslations('name') as $name) {
            if (in_array($plain((string) $name), $wanted, true)) {
                return true;
            }
        }

        return false;
    }
}
