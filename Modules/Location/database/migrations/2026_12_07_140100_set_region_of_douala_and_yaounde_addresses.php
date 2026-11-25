<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les adresses déjà enregistrées au Cameroun (décision 2026-11-25-region-de-l-adresse) : Yaoundé dans la région
 * Centre, Douala dans le Littoral. La région est reprise par son nom français dans le pays, créée si elle manque.
 * Seules les adresses sans région sont touchées.
 */
return new class extends Migration
{
    /** Ville (sans casse ni accent) => nom de la région. */
    private const REGIONS = [
        'yaounde' => ['fr' => 'Centre', 'en' => 'Centre'],
        'douala' => ['fr' => 'Littoral', 'en' => 'Littoral'],
    ];

    public function up(): void
    {
        $country = DB::table('countries')->where('code', 'CM')->value('id');
        if ($country === null) {
            return;
        }

        $addresses = DB::table('addresses')->where('country_id', $country)->whereNull('region_id')->get(['id', 'city']);
        foreach (self::REGIONS as $city => $names) {
            $ids = $addresses->filter(fn (object $address): bool => $this->plain((string) $address->city) === $city)->pluck('id');
            if ($ids->isEmpty()) {
                continue;
            }

            DB::table('addresses')->whereIn('id', $ids)->update(['region_id' => $this->region((int) $country, $names)]);
        }
    }

    /** Ces adresses perdent leur région avec la colonne (migration précédente) : rien à faire ici. */
    public function down(): void {}

    /** La ville sans casse, sans accent ni espaces en trop (« Yaoundé » => « yaounde »). */
    private function plain(string $city): string
    {
        return strtolower(trim((string) iconv('UTF-8', 'ASCII//TRANSLIT', $city)));
    }

    /**
     * La région du pays qui porte ce nom français, créée à la fin du pays si elle manque.
     *
     * @param  array{fr: string, en: string}  $names
     */
    private function region(int $country, array $names): int
    {
        $existing = DB::table('regions')->where('country_id', $country)->get(['id', 'name'])
            ->first(fn (object $region): bool => trim((string) (json_decode((string) $region->name, true)['fr'] ?? '')) === $names['fr']);
        if ($existing !== null) {
            return (int) $existing->id;
        }

        return (int) DB::table('regions')->insertGetId([
            'country_id' => $country,
            'name' => json_encode($names),
            'active' => true,
            'position' => (int) DB::table('regions')->where('country_id', $country)->max('position') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
