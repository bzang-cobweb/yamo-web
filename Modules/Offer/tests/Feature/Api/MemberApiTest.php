<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Booking;
use Tests\TestCase;

/**
 * La page publique d'un membre (décision 2026-11-19-page-membre) : photo, nom,
 * « Membre depuis », présentation, ville, séjours acceptés ; jamais ses coordonnées.
 */
class MemberApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    public function test_a_signed_in_account_sees_the_public_page_of_a_member_without_contact_details(): void
    {
        $member = User::factory()->create([
            'first_name' => 'Paul', 'last_name' => 'Etoa', 'description' => 'Réalisateur de clips.',
            'email' => 'paul@example.test', 'phone' => '+237690000099', 'created_at' => '2026-03-10 10:00:00',
        ]);
        Booking::factory()->for($member)->status(BookingStatusEnum::ACCEPTED)->count(2)->create();
        Booking::factory()->for($member)->create();
        $viewer = User::factory()->create(['locale' => 'fr']);

        $this->withHeaders(['X-Country' => 'CM'])->getJson("/api/v1/members/{$member->getKey()}")->assertUnauthorized();

        $response = $this->withToken($viewer->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM'])
            ->getJson("/api/v1/members/{$member->getKey()}")
            ->assertOk();

        $response->assertJsonPath('data.title', 'Profil')
            ->assertJsonPath('data.name', 'Paul Etoa')
            ->assertJsonPath('data.since', 'Membre depuis mars 2026')
            ->assertJsonPath('data.description', 'Réalisateur de clips.')
            ->assertJsonPath('data.facts', [['label' => 'Séjours', 'value' => '2']]);
        $this->assertStringNotContainsString('paul@example.test', (string) $response->getContent());
        $this->assertStringNotContainsString('690000099', (string) $response->getContent());

        $this->withToken($viewer->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM'])->getJson('/api/v1/members/999999')->assertNotFound();
    }
}
