<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Menu du profil (`GET /api/v1/auth/me/menu`) : liens définis dans
 * `ProfileMenuService`, dans leur ordre, filtrés par canal, libellés selon le compte.
 */
class ProfileMenuApiTest extends TestCase
{
    use RefreshDatabase;

    private function menu(User $user): array
    {
        return $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/auth/me/menu', ['Accept-Language' => 'fr'])
            ->assertOk()
            ->json('data');
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(['locale' => 'fr', ...$attributes]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me/menu')->assertUnauthorized();
    }

    private function submenu(User $user, string $menu): array
    {
        return $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/auth/me/menu/'.$menu, ['Accept-Language' => 'fr'])
            ->assertOk()
            ->json();
    }

    public function test_lists_the_submenus_then_the_forms_and_lists_in_the_service_order(): void
    {
        $menu = $this->menu($this->user(['email' => 'jeanne@example.test', 'phone' => null]));

        // « Mes partenaires » (module Partner) vient après le compte (décision 2026-11-24-partenaires-par-les-membres).
        $this->assertSame(['personal', 'privacy', 'notifications', 'preferences', 'addresses', 'partners'], array_column($menu, 'key'));
        $this->assertSame(['Informations personnelles', 'Confidentialité', 'Notifications', 'Préférences', 'Mes adresses', 'Mes partenaires'], array_column($menu, 'label'));
        // Un sous-menu (`menu`), un formulaire (`form`) ou une liste (`list`), et une icône par lien
        // (décision 2026-11-27-menu-profil-icones-sous-menus).
        $this->assertSame(['personal', 'privacy'], array_values(array_filter(array_column($menu, 'menu'))));
        $this->assertSame(['ios' => 'person.crop.circle', 'android' => 'account_circle'], $menu[0]['icon']);
        $this->assertSame(['ios' => 'person.3', 'android' => 'groups'], $menu[5]['icon']);
        $this->assertSame([
            'key' => 'addresses', 'label' => 'Mes adresses', 'icon' => ['ios' => 'mappin.and.ellipse', 'android' => 'location_on'],
            'list' => 'auth.addresses', 'group' => ['key' => 'account', 'label' => 'Compte'],
        ], $menu[4]);
    }

    public function test_the_personal_submenu_holds_the_details_email_phone_password_and_identity(): void
    {
        $submenu = $this->submenu($this->user(['email' => 'jeanne@example.test', 'phone' => null]), 'personal');

        $this->assertSame('Informations personnelles', $submenu['title']);
        $this->assertSame(['details', 'email', 'phone', 'password', 'residence', 'emergency-contact', 'identity'], array_column($submenu['data'], 'key'));
        $this->assertSame(
            ['auth.profile.personal', 'auth.profile.email', 'auth.profile.password', 'auth.profile.residence', 'auth.profile.emergency-contact', 'auth.identity'],
            array_column($submenu['data'], 'form'),
        );
        // « Mes numéros » : une liste, le principal puis les supplémentaires (décision 2026-11-27-plusieurs-numeros).
        $this->assertSame(['key' => 'phone', 'label' => 'Mes numéros', 'list' => 'auth.phones'], array_intersect_key($submenu['data'][2], array_flip(['key', 'label', 'list'])));
        $this->assertArrayNotHasKey('form', $submenu['data'][2]);
        $this->assertSame(['ios' => 'envelope', 'android' => 'mail'], $submenu['data'][1]['icon']);
    }

    public function test_the_privacy_submenu_holds_visibility_reviews_data_and_the_account_deletion(): void
    {
        $submenu = $this->submenu($this->user(), 'privacy');

        // Décision 2026-11-27-confidentialite.
        $this->assertSame('Confidentialité', $submenu['title']);
        $this->assertSame(['profile-visibility', 'review-privacy', 'personal-data', 'delete-account'], array_column($submenu['data'], 'key'));
        $this->assertSame(['Visibilité du profil', 'Avis', 'Mes données', 'Supprimer mon compte'], array_column($submenu['data'], 'label'));
        $this->assertSame(
            ['auth.profile.privacy.visibility', 'auth.profile.privacy.reviews', 'auth.profile.privacy.data', 'auth.profile.delete-account'],
            array_column($submenu['data'], 'form'),
        );
        $this->assertSame(['ios' => 'eye', 'android' => 'visibility'], $submenu['data'][0]['icon']);
    }

    public function test_an_unknown_submenu_or_a_link_that_is_not_one_is_not_found(): void
    {
        $this->getJson('/api/v1/auth/me/menu/personal')->assertUnauthorized();
        $user = $this->user();

        foreach (['unknown', 'preferences', 'details'] as $menu) {
            $this->withToken($user->createToken('test')->plainTextToken)->getJson('/api/v1/auth/me/menu/'.$menu)->assertNotFound();
        }
    }

    public function test_a_disabled_channel_removes_its_link(): void
    {
        config(['auth.channels' => ['email' => false, 'phone' => true]]);

        $this->assertSame(['details', 'phone', 'password', 'residence', 'emergency-contact', 'identity'], array_column($this->submenu($this->user(), 'personal')['data'], 'key'));
    }

    public function test_labels_follow_the_account_language(): void
    {
        $menu = $this->submenu($this->user(['locale' => 'en', 'email' => null, 'phone' => '+41 79 123 45 67']), 'personal')['data'];

        $this->assertSame('Add an email address', collect($menu)->firstWhere('key', 'email')['label']);
        // « Mes numéros », quel que soit le compte (décision 2026-11-27-plusieurs-numeros).
        $this->assertSame('My phone numbers', collect($menu)->firstWhere('key', 'phone')['label']);
    }
}
