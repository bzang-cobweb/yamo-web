<?php

namespace Modules\Partner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Tests\TestCase;

/**
 * La policy des partenaires dans l'app, par où passent toutes les routes de l'API (décision
 * 2026-11-24-partenaires-policy-routes).
 */
class PartnerPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cameroon = Country::factory()->create(['code' => 'CM']);
    }

    private function user(RoleEnum $role = RoleEnum::MEMBER): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate($role->value, 'web'));

        return $user;
    }

    private function api(?User $user, string $method, string $uri, array $data = [])
    {
        $this->app['auth']->forgetGuards();
        $request = $this->flushHeaders()->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);
        if ($user !== null) {
            $request = $request->withToken($user->createToken('iPhone')->plainTextToken);
        }

        return $request->json($method, $uri, $data);
    }

    public function test_a_visible_partner_is_seen_by_everyone_a_pending_one_only_by_its_accounts(): void
    {
        $member = $this->user();
        $visible = Partner::factory()->for($this->cameroon)->create();
        $pending = Partner::factory()->for($this->cameroon)->create(['status' => PartnerStatusEnum::PENDING]);
        $pending->users()->attach($member->getKey());

        $this->assertTrue(Gate::forUser(null)->allows('viewAny', Partner::class));
        $this->assertTrue(Gate::forUser(null)->allows('view', $visible));
        $this->assertTrue(Gate::forUser(null)->inspect('view', $pending)->status() === 404);
        $this->assertTrue(Gate::forUser($member)->allows('view', $pending));

        $this->api(null, 'GET', '/api/v1/partners')->assertOk();
        $this->api(null, 'GET', "/api/v1/partners/{$visible->getKey()}")->assertOk();
        $this->api(null, 'GET', "/api/v1/partners/{$pending->getKey()}")->assertNotFound();
        $this->api($member, 'GET', "/api/v1/partners/{$pending->getKey()}")->assertOk();
        // Un clic : visible seulement, même pour un compte rattaché.
        $this->api($member, 'POST', "/api/v1/partners/{$pending->getKey()}/click")->assertNotFound();
        $this->api(null, 'POST', "/api/v1/partners/{$visible->getKey()}/click")->assertNoContent();
    }

    public function test_in_the_app_a_super_admin_follows_the_rule_of_every_account(): void
    {
        $superAdmin = $this->user(RoleEnum::SUPER_ADMIN);
        $partner = Partner::factory()->for($this->cameroon)->create(['status' => PartnerStatusEnum::PENDING]);

        // Pas rattaché : le partenaire n'existe pas pour lui dans l'app.
        $this->assertSame(404, Gate::forUser($superAdmin)->inspect('update', $partner)->status());
        $this->api($superAdmin, 'GET', "/api/v1/partners/{$partner->getKey()}")->assertNotFound();
        $this->api($superAdmin, 'GET', "/api/v1/forms/partner.partner?record={$partner->getKey()}")->assertNotFound();
        $this->api($superAdmin, 'PATCH', "/api/v1/partners/{$partner->getKey()}", ['name' => 'Pirate', 'description' => 'Texte.'])->assertNotFound();
        $this->api($superAdmin, 'DELETE', "/api/v1/partners/{$partner->getKey()}")->assertNotFound();

        // Rattaché sans être administrateur : il modifie, il ne retire pas.
        $partner->users()->attach($superAdmin->getKey(), ['is_admin' => false]);
        $this->api($superAdmin, 'PATCH', "/api/v1/partners/{$partner->getKey()}", ['name' => 'Modifié', 'description' => 'Texte.'])->assertNoContent();
        $this->api($superAdmin, 'DELETE', "/api/v1/partners/{$partner->getKey()}")->assertForbidden();
        // Proposer : comme tout compte.
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Partner::class));
    }

    public function test_another_country_partner_does_not_exist(): void
    {
        $member = $this->user();
        $senegal = Country::factory()->create(['code' => 'SN', 'active' => false]);
        $partner = Partner::factory()->for($senegal)->create();
        $partner->users()->attach($member->getKey(), ['is_admin' => true]);

        $this->api(null, 'GET', "/api/v1/partners/{$partner->getKey()}")->assertNotFound();
        $this->api($member, 'PATCH', "/api/v1/partners/{$partner->getKey()}", ['name' => 'X', 'description' => 'Y'])->assertNotFound();
    }
}
