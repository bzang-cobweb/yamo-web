<?php

namespace Tests\Feature\Models;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Models\Country;
use Modules\Partner\Models\Partner;
use Tests\TestCase;

/**
 * Les traits `HasCountry` et `HasPaymentRange` (décision 2026-11-24-visibilite-pays-paiement), essayés sur les partenaires.
 */
class CountryAndPaymentRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_visible_country_takes_the_given_country_or_else_the_current_one(): void
    {
        $cameroon = Country::factory()->create(['code' => 'CM']);
        $gabon = Country::factory()->create(['code' => 'GA']);
        $douala = Partner::factory()->for($cameroon)->create();
        $libreville = Partner::factory()->for($gabon)->create();
        request()->headers->set('X-Country', 'CM');

        $this->assertSame([$douala->getKey()], Partner::query()->visibleCountry()->pluck('id')->all());
        $this->assertSame([$libreville->getKey()], Partner::query()->visibleCountry($gabon)->pluck('id')->all());
        $this->assertSame([$libreville->getKey()], Partner::query()->visibleCountry((int) $gabon->getKey())->pluck('id')->all());
        $this->assertTrue($douala->isInCountry());
        $this->assertFalse($libreville->isInCountry());
        $this->assertTrue($libreville->isInCountry($gabon));
    }

    public function test_visible_range_keeps_the_paid_period_only_when_payment_is_required(): void
    {
        $country = Country::factory()->create();
        $paid = Partner::factory()->for($country)->create(['starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);
        $expired = Partner::factory()->for($country)->create(['starts_at' => null, 'expires_at' => now()->subDay()]);
        $later = Partner::factory()->for($country)->create(['starts_at' => now()->addDay(), 'expires_at' => now()->addMonth()]);
        $undated = Partner::factory()->for($country)->create(['starts_at' => null, 'expires_at' => null]);

        // Non appliqué : tous.
        $this->assertCount(4, Partner::query()->visiblePaymentRange()->get());
        $this->assertTrue($undated->isInPaymentRange());

        // Appliqué : la période payée seule, commencée et pas terminée ; sans date de fin, jamais.
        Setting::current()->update(['partner_payment_required' => true]);
        $this->assertSame([$paid->getKey()], Partner::query()->visiblePaymentRange()->pluck('id')->all());
        $this->assertSame([true, false, false, false], [$paid->isInPaymentRange(), $expired->isInPaymentRange(), $later->isInPaymentRange(), $undated->isInPaymentRange()]);
        // Le réglage déjà lu, passé par une liste.
        $this->assertTrue($undated->isInPaymentRange(false));

        // Commencée : sans début, ou début passé, que le paiement soit appliqué ou non.
        $this->assertEqualsCanonicalizing([$paid->getKey(), $expired->getKey(), $undated->getKey()], Partner::query()->paymentStarted()->pluck('id')->all());
        $this->assertFalse($later->isPaymentStarted());
    }
}
