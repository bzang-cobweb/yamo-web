<?php

namespace Bza\Form\Support;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Carbon;

/**
 * All dates shown to the app are formatted here, on the server (decision 2026-11-25-dates-formatees-serveur): in the
 * request's language and in the time zone of the current country space (`X-Country` → `Africa/Douala`), provided by
 * the Location module (`resolveZoneUsing`); without a country, the application's time zone. Two screens therefore
 * always show the same date for the same moment.
 */
final class DisplayTime
{
    /** @var (Closure(): ?string)|null */
    private static ?Closure $zoneResolver = null;

    /**
     * What provides the request's time zone (the current country); `null`: the application's.
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

    /** The instant in the reader's time zone and language. */
    public static function at(CarbonInterface|string $at, ?string $locale = null): Carbon
    {
        return Carbon::parse($at)->setTimezone(self::zone())->locale($locale ?? app()->getLocale());
    }

    /** A date only (`ll`: « 18 nov. 2026 », `LL`: « 18 novembre 2026 »), or `null`. */
    public static function date(CarbonInterface|string|null $at, string $format = 'll', ?string $locale = null): ?string
    {
        if ($at === null || $at === '') {
            return null;
        }

        return self::at($at, $locale)->isoFormat($format);
    }

    /**
     * A day without a time (`YYYY-MM-DD`, a deletion date), formatted without changing time zone: the 12th stays the 12th.
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
     * The date of a card, relative (decision 2026-11-22-date-carte-heure): « à l'instant », « il y a 5 min », the time
     * today (« 10:30 »), « hier à 10:30 », otherwise « 3 mars 2027 10:30 ». Days are counted on the calendar of the
     * time zone.
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
     * The moment of a message: the time alone today (« 10:30 »), otherwise the date and time (« 18 nov. 2026 10:30 »);
     * `full`: the day spelled out (« mercredi 18 novembre 2026 10:30 »), for a notification's detail.
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
