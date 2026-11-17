<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\ProfileMenuKey;
use Modules\Auth\Models\ProfileMenuLink;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Menu du profil (`GET /api/v1/auth/me/menu`) : liens actifs du back-office, dans
 * leur ordre, filtrés par canal, libellés selon le compte.
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

    public function test_lists_the_five_screens_in_their_initial_order(): void
    {
        $menu = $this->menu($this->user(['email' => 'jeanne@example.test', 'phone' => null]));

        $this->assertSame(['personal', 'email', 'phone', 'password', 'preferences'], array_column($menu, 'key'));
        $this->assertSame(
            ['Informations personnelles', "Changer d'adresse e-mail", 'Ajouter un numéro', 'Changer le mot de passe', 'Préférences'],
            array_column($menu, 'label'),
        );
    }

    public function test_follows_the_back_office_order_and_hides_inactive_links(): void
    {
        ProfileMenuLink::query()->where('key', ProfileMenuKey::PREFERENCES)->update(['position' => 0]);
        ProfileMenuLink::query()->where('key', ProfileMenuKey::PASSWORD)->update(['active' => false]);

        $this->assertSame(['preferences', 'personal', 'email', 'phone'], array_column($this->menu($this->user()), 'key'));
    }

    public function test_a_disabled_channel_removes_its_link(): void
    {
        config(['auth.channels' => ['email' => false, 'phone' => true]]);

        $this->assertSame(['personal', 'phone', 'password', 'preferences'], array_column($this->menu($this->user()), 'key'));
    }

    public function test_labels_follow_the_account_language(): void
    {
        $menu = $this->menu($this->user(['locale' => 'en', 'email' => null, 'phone' => '+41791234567']));

        $this->assertSame('Add an email address', collect($menu)->firstWhere('key', 'email')['label']);
        $this->assertSame('Change phone number', collect($menu)->firstWhere('key', 'phone')['label']);
    }
}
