<?php

namespace Modules\Location\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

/**
 * Les 26 cantons suisses, rattachés au pays CH (CountrySeeder d'abord).
 *
 * Rejouable : un canton déjà présent (même nom français ou anglais dans CH) voit
 * ses noms mis à jour et garde son `active`.
 */
class RegionSeeder extends Seeder
{
    /**
     * Noms officiels français, noms usuels anglais.
     *
     * @var list<array{fr: string, en: string}>
     */
    private const SWISS_CANTONS = [
        ['fr' => 'Argovie', 'en' => 'Aargau'],
        ['fr' => 'Appenzell Rhodes-Extérieures', 'en' => 'Appenzell Ausserrhoden'],
        ['fr' => 'Appenzell Rhodes-Intérieures', 'en' => 'Appenzell Innerrhoden'],
        ['fr' => 'Bâle-Campagne', 'en' => 'Basel-Landschaft'],
        ['fr' => 'Bâle-Ville', 'en' => 'Basel-Stadt'],
        ['fr' => 'Berne', 'en' => 'Bern'],
        ['fr' => 'Fribourg', 'en' => 'Fribourg'],
        ['fr' => 'Genève', 'en' => 'Geneva'],
        ['fr' => 'Glaris', 'en' => 'Glarus'],
        ['fr' => 'Grisons', 'en' => 'Grisons'],
        ['fr' => 'Jura', 'en' => 'Jura'],
        ['fr' => 'Lucerne', 'en' => 'Lucerne'],
        ['fr' => 'Neuchâtel', 'en' => 'Neuchâtel'],
        ['fr' => 'Nidwald', 'en' => 'Nidwalden'],
        ['fr' => 'Obwald', 'en' => 'Obwalden'],
        ['fr' => 'Saint-Gall', 'en' => 'St. Gallen'],
        ['fr' => 'Schaffhouse', 'en' => 'Schaffhausen'],
        ['fr' => 'Schwytz', 'en' => 'Schwyz'],
        ['fr' => 'Soleure', 'en' => 'Solothurn'],
        ['fr' => 'Tessin', 'en' => 'Ticino'],
        ['fr' => 'Thurgovie', 'en' => 'Thurgau'],
        ['fr' => 'Uri', 'en' => 'Uri'],
        ['fr' => 'Valais', 'en' => 'Valais'],
        ['fr' => 'Vaud', 'en' => 'Vaud'],
        ['fr' => 'Zoug', 'en' => 'Zug'],
        ['fr' => 'Zurich', 'en' => 'Zurich'],
    ];

    public function run(): void
    {
        $switzerland = Country::query()->where('code', 'CH')->firstOrFail();

        foreach (self::SWISS_CANTONS as $names) {
            $canton = $switzerland->regions()
                ->where(fn ($query) => $query
                    ->where('name->fr', $names['fr'])
                    ->orWhere('name->en', $names['en']))
                ->first() ?? new Region(['country_id' => $switzerland->getKey(), 'active' => true]);

            $canton->setTranslations('name', $names);
            $canton->save();
        }
    }
}
