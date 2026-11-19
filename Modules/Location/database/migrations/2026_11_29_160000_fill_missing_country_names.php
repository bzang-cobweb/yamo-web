<?php

use App\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Location\Database\Seeders\CountrySeeder;

/**
 * Les noms des pays dans une langue ajoutée à l'app (l'allemand, décision
 * 2026-11-19-contrat-langue-saisie-allemand), d'après ICU ; les noms déjà présents
 * ne sont pas touchés. Sans retour arrière : un nom en trop ne gêne pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        $seeder = new CountrySeeder;

        DB::table('countries')->orderBy('id')->each(function (object $country) use ($seeder): void {
            $names = (array) json_decode((string) $country->name, true);
            $missing = array_diff(Locales::values(), array_keys(array_filter($names)));

            foreach ($missing as $locale) {
                $names[$locale] = $seeder->regionName((string) $country->code, $locale) ?? (string) $country->code;
            }

            if ($missing !== []) {
                DB::table('countries')->where('id', $country->id)->update(['name' => json_encode($names, JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    public function down(): void {}
};
