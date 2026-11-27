<?php

namespace Tests\Feature;

use Bza\Form\Support\DisplayTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Les dates montrées à l'app, formatées par le serveur dans le fuseau du pays courant (décision
 * 2026-11-25-dates-formatees-serveur).
 */
class DisplayTimeTest extends TestCase
{
    use RefreshDatabase;

    private function inCountry(string $code, string $zone): void
    {
        Country::factory()->create(['code' => $code, 'timezone' => $zone]);
        request()->attributes->remove('location.current_country');
        request()->headers->set('X-Country', $code);
        app()->setLocale('fr');
    }

    public function test_the_zone_is_the_current_country_one_otherwise_the_application_one(): void
    {
        $this->assertSame('UTC', DisplayTime::zone());

        $this->inCountry('CM', 'Africa/Douala');
        $this->assertSame('Africa/Douala', DisplayTime::zone());
        // 23:30 UTC le 18 : déjà le 19 à Douala.
        $this->assertSame('19 nov. 2026', DisplayTime::date('2026-11-18T23:30:00+00:00'));
        $this->assertSame('19 novembre 2026', DisplayTime::date('2026-11-18T23:30:00+00:00', 'LL'));
    }

    public function test_a_day_without_time_never_moves(): void
    {
        $this->inCountry('US', 'America/New_York');

        $this->assertSame('20 décembre 2026', DisplayTime::day('2026-12-20'));
    }

    public function test_relative_and_stamp_count_days_in_the_country(): void
    {
        $this->inCountry('CM', 'Africa/Douala');
        $this->travelTo(Carbon::parse('2026-11-19 10:00:00', 'UTC'));

        $this->assertSame("à l'instant", DisplayTime::relative(now()->subSeconds(20)));
        $this->assertSame('il y a 5 min', DisplayTime::relative(now()->subMinutes(5)));
        // 23:30 UTC le 18 : 00:30 le 19 à Douala, donc aujourd'hui, pas hier.
        $this->assertSame('00:30', DisplayTime::relative('2026-11-18T23:30:00+00:00'));
        $this->assertSame('hier à 22:30', DisplayTime::relative('2026-11-18T21:30:00+00:00'));
        $this->assertSame('3 janv. 2026 11:00', DisplayTime::relative('2026-01-03T10:00:00+00:00'));

        $this->assertSame('00:30', DisplayTime::stamp('2026-11-18T23:30:00+00:00'));
        $this->assertSame('18 nov. 2026 22:30', DisplayTime::stamp('2026-11-18T21:30:00+00:00'));
        $this->assertSame('mercredi 18 novembre 2026 22:30', DisplayTime::stamp('2026-11-18T21:30:00+00:00', full: true));
    }
}
