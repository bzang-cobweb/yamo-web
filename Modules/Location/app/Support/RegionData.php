<?php

namespace Modules\Location\Support;

/**
 * Les régions connues d'un pays et leur nom (« Canton », « District »…), un fichier par pays dans
 * `database/data/regions/<CODE>.php` (décision 2026-11-25-regions-par-pays) : ICU, d'où viennent les pays, n'a pas
 * leurs subdivisions. Lu par `CountrySeeder` (le nom des régions) et `RegionSeeder` (les régions).
 */
class RegionData
{
    /**
     * Les codes des pays qui ont un fichier, par ordre alphabétique.
     *
     * @return list<string>
     */
    public static function countries(): array
    {
        $codes = array_map(fn (string $file): string => strtoupper(basename($file, '.php')), glob(self::directory().'/*.php') ?: []);
        sort($codes);

        return $codes;
    }

    /**
     * Les données du pays, ou `null` sans fichier.
     *
     * @return array{label: array<string, string>, regions: list<array<string, string>>}|null
     */
    public static function of(string $code): ?array
    {
        $file = self::directory().'/'.strtoupper($code).'.php';
        if (! is_file($file)) {
            return null;
        }

        return require $file;
    }

    private static function directory(): string
    {
        return module_path('Location', 'database/data/regions');
    }
}
