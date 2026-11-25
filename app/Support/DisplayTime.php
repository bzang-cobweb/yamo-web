<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Toutes les dates montrées à l'app sont formatées ici, côté serveur (décision 2026-11-25-dates-formatees-serveur) :
 * dans la langue de la requête et dans le fuseau de l'espace pays courant (`X-Country` → `Africa/Douala`), que le
 * module Location fournit (`resolveZoneUsing`) ; sans pays, le fuseau de l'application. Deux écrans montrent donc
 * toujours la même date pour le même moment.
 */
final class DisplayTime
{
    /** @var (Closure(): ?string)|null */
    private static ?Closure $zoneResolver = null;

    /**
     * Qui donne le fuseau de la requête (le pays courant) ; `null` : celui de l'application.
     *
     * @param  (Closure(): ?string)|null  $resolver
     */
    public static function resolveZoneUsing(?Closure $resolver): void
    {
        self::$zoneResolver = $resolver;
    }

    public static function zone(): string
    {
        $zone = self::$zoneResolver === null ? null : (self::$zoneResolver)();

        return is_string($zone) && $zone !== '' ? $zone : (string) config('app.timezone');
    }

    /** L'instant dans le fuseau et la langue de qui lit. */
    public static function at(CarbonInterface|string $at, ?string $locale = null): Carbon
    {
        return Carbon::parse($at)->setTimezone(self::zone())->locale($locale ?? app()->getLocale());
    }

    /** Une date seule (`ll` : « 18 nov. 2026 », `LL` : « 18 novembre 2026 »), ou `null`. */
    public static function date(CarbonInterface|string|null $at, string $format = 'll', ?string $locale = null): ?string
    {
        if ($at === null || $at === '') {
            return null;
        }

        return self::at($at, $locale)->isoFormat($format);
    }

    /**
     * Un jour sans heure (`AAAA-MM-JJ`, une date de suppression), formaté sans changer de fuseau : le 12 reste le 12.
     */
    public static function day(CarbonInterface|string|null $day, string $format = 'LL', ?string $locale = null): ?string
    {
        if ($day === null || $day === '') {
            return null;
        }
        $iso = $day instanceof CarbonInterface ? $day->toDateString() : substr($day, 0, 10);

        return Carbon::parse($iso)->locale($locale ?? app()->getLocale())->isoFormat($format);
    }

    /**
     * La date d'une carte, relative (décision 2026-11-22-date-carte-heure) : « à l'instant », « il y a 5 min », l'heure
     * aujourd'hui (« 10:30 »), « hier à 10:30 », sinon « 3 mars 2027 10:30 ». Les jours se comptent au calendrier du
     * fuseau.
     */
    public static function relative(CarbonInterface|string|null $at, ?string $locale = null): ?string
    {
        if ($at === null || $at === '') {
            return null;
        }

        $locale ??= app()->getLocale();
        $date = self::at($at, $locale);
        $now = Carbon::now(self::zone());
        $seconds = max(0, $now->getTimestamp() - $date->getTimestamp());

        if ($seconds < 60) {
            return (string) __('notification::mobile.notifications.justNow', [], $locale);
        }
        if ($seconds < 3600) {
            return (string) __('notification::mobile.notifications.minutesAgo', ['count' => intdiv($seconds, 60)], $locale);
        }

        $time = $date->isoFormat('LT');
        $days = (int) $date->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());

        if ($days === 0) {
            return $time;
        }
        if ($days === 1) {
            return (string) __('notification::mobile.notifications.yesterdayAt', ['time' => $time], $locale);
        }

        return $date->isoFormat('ll').' '.$time;
    }

    /**
     * Le moment d'un message : l'heure seule aujourd'hui (« 10:30 »), sinon la date et l'heure (« 18 nov. 2026 10:30 ») ;
     * `full` : le jour en toutes lettres (« mercredi 18 novembre 2026 10:30 »), pour le détail d'une notification.
     */
    public static function stamp(CarbonInterface|string|null $at, bool $full = false, ?string $locale = null): ?string
    {
        if ($at === null || $at === '') {
            return null;
        }

        $date = self::at($at, $locale);

        if ($date->isSameDay(Carbon::now(self::zone()))) {
            return $date->isoFormat('LT');
        }

        return $date->isoFormat($full ? 'dddd LL LT' : 'll LT');
    }
}
