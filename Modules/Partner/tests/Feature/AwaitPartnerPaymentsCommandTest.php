<?php

namespace Modules\Partner\Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerAwaitingPayment;
use Tests\TestCase;

/**
 * `partner:await-payment` : le paiement appliqué, les partenaires publiés dont la période payée est finie ou absente
 * passent en attente de paiement (décision 2026-11-24-partenaire-attente-paiement-commande).
 */
class AwaitPartnerPaymentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_partners_without_a_running_paid_period_await_payment(): void
    {
        $country = Country::factory()->create();
        $make = fn (PartnerStatusEnum $status, mixed $expires): Partner => Partner::factory()->for($country)->create(['status' => $status, 'expires_at' => $expires]);
        $ended = $make(PartnerStatusEnum::PUBLISHED, today()->subDay());
        $undated = $make(PartnerStatusEnum::PUBLISHED, null);
        // Son dernier jour, aujourd'hui : encore payé (décision 2026-11-24-periode-payee-en-jours).
        $lastDay = $make(PartnerStatusEnum::PUBLISHED, today());
        $paid = $make(PartnerStatusEnum::PUBLISHED, today()->addMonth());
        $pending = $make(PartnerStatusEnum::PENDING, null);
        $rejected = $make(PartnerStatusEnum::REJECTED, today()->subDay());

        // Le paiement non appliqué : rien.
        $this->artisan('partner:await-payment')->assertSuccessful();
        $this->assertSame(PartnerStatusEnum::PUBLISHED, $ended->fresh()->status);

        Setting::current()->update(['partner_payment_required' => true]);
        $this->artisan('partner:await-payment')->expectsOutput('2 partenaires passés en attente de paiement.')->assertSuccessful();

        $statuses = fn (Partner ...$partners): array => array_map(fn (Partner $partner): PartnerStatusEnum => $partner->fresh()->status, $partners);
        $this->assertSame(array_fill(0, 2, PartnerStatusEnum::AWAITING_PAYMENT), $statuses($ended, $undated));
        $this->assertSame([PartnerStatusEnum::PUBLISHED, PartnerStatusEnum::PUBLISHED, PartnerStatusEnum::PENDING, PartnerStatusEnum::REJECTED], $statuses($lastDay, $paid, $pending, $rejected));

        // Rejouée : plus rien à faire.
        $this->artisan('partner:await-payment')->expectsOutput('Aucun partenaire passé en attente de paiement.')->assertSuccessful();
    }

    public function test_the_change_is_announced_like_a_back_office_one(): void
    {
        Notification::fake();
        Setting::current()->update(['partner_payment_required' => true]);
        $partner = Partner::factory()->for(Country::factory()->create())->create(['status' => PartnerStatusEnum::PUBLISHED, 'expires_at' => today()->subDay()]);
        $admin = User::factory()->create(['locale' => 'fr']);
        $partner->users()->attach($admin->getKey(), ['is_admin' => true]);

        $this->artisan('partner:await-payment')->assertSuccessful();

        // Ses administrateurs, par e-mail et SMS (décision 2026-11-24-partenaire-admins-email-sms).
        Notification::assertSentTo($admin, PartnerAwaitingPayment::class, fn (PartnerAwaitingPayment $notification): bool => $notification->toArray($admin)['title'] === 'Partenaire en attente de paiement');
    }
}
