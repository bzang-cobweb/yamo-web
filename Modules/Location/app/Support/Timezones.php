<?php

namespace Modules\Location\Support;

use DateTimeZone;

/**
 * Fuseau horaire d'un pays d'après la base de fuseaux de PHP (CM → Africa/Douala, CH → Europe/Zurich, CI →
 * Africa/Abidjan ; décision 2026-11-25-fuseau-par-pays). Un pays à plusieurs fuseaux prend celui de sa capitale
 * (`MAIN`) ; modifiable ensuite au back-office.
 */
final class Timezones
{
    /**
     * Le fuseau de la capitale, pour les pays à plusieurs fuseaux dont le premier de la liste n'est pas celui-là.
     *
     * @var array<string, string>
     */
    private const MAIN = [
        'AQ' => 'Antarctica/McMurdo',
        'AU' => 'Australia/Sydney',
        'BR' => 'America/Sao_Paulo',
        'CA' => 'America/Toronto',
        'CL' => 'America/Santiago',
        'CY' => 'Asia/Nicosia',
        'ES' => 'Europe/Madrid',
        'FM' => 'Pacific/Pohnpei',
        'GL' => 'America/Nuuk',
        'KI' => 'Pacific/Tarawa',
        'MH' => 'Pacific/Majuro',
        'MN' => 'Asia/Ulaanbaatar',
        'MX' => 'America/Mexico_City',
        'PF' => 'Pacific/Tahiti',
        'PG' => 'Pacific/Port_Moresby',
        'PT' => 'Europe/Lisbon',
        'RU' => 'Europe/Moscow',
        'UM' => 'Pacific/Wake',
        'US' => 'America/New_York',
        'UZ' => 'Asia/Tashkent',
    ];

    public static function forCountry(string $code): ?string
    {
        $code = strtoupper($code);
        $zones = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $code);
        $main = self::MAIN[$code] ?? null;

        if ($main !== null && in_array($main, $zones, true)) {
            return $main;
        }

        return $zones[0] ?? null;
    }
}
