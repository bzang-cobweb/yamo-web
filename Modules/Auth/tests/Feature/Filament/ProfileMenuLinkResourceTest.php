<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\ProfileMenuKey;
use Modules\Auth\Filament\Resources\ProfileMenuLinkResource;
use Modules\Auth\Filament\Resources\ProfileMenuLinkResource\Pages\ListProfileMenuLinks;
use Modules\Auth\Models\ProfileMenuLink;

/**
 * Menu du profil dans le back-office : ordre et activation, avec la permission des
 * réglages ; ni création ni suppression.
 */
class ProfileMenuLinkResourceTest extends AuthFilamentTestCase
{
    private function link(ProfileMenuKey $key): ProfileMenuLink
    {
        return ProfileMenuLink::query()->where('key', $key)->sole();
    }

    public function test_lists_the_links_in_order(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);

        Livewire::test(ListProfileMenuLinks::class)
            ->assertOk()
            ->assertCanSeeTableRecords(ProfileMenuLink::query()->ordered()->get(), inOrder: true)
            ->assertSee('Informations personnelles');
    }

    public function test_reorders_and_deactivates_links(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);
        $order = ProfileMenuLink::query()->ordered()->pluck('id')->reverse()->map(fn (int $id): string => (string) $id)->values()->all();

        Livewire::test(ListProfileMenuLinks::class)
            ->call('reorderTable', $order)
            ->call('updateTableColumnState', 'active', (string) $this->link(ProfileMenuKey::PHONE)->getKey(), false);

        $this->assertSame(
            ['preferences', 'password', 'phone', 'email', 'personal'],
            ProfileMenuLink::query()->ordered()->get()->map(fn (ProfileMenuLink $link): string => $link->key->value)->all(),
        );
        $this->assertFalse($this->link(ProfileMenuKey::PHONE)->active);
    }

    public function test_is_closed_without_the_settings_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS);

        $this->get(ProfileMenuLinkResource::getUrl())->assertForbidden();
    }

    public function test_links_can_be_neither_created_nor_deleted(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);

        $this->assertFalse(ProfileMenuLinkResource::canCreate());
        $this->assertFalse(ProfileMenuLinkResource::canDelete($this->link(ProfileMenuKey::EMAIL)));
        $this->assertArrayNotHasKey('create', ProfileMenuLinkResource::getPages());
    }

    public function test_not_even_a_super_admin_creates_or_deletes_links(): void
    {
        $this->actingAsSuperAdmin();

        $this->assertFalse(ProfileMenuLinkResource::canCreate());
        $this->assertFalse(ProfileMenuLinkResource::canDelete($this->link(ProfileMenuKey::EMAIL)));
        $this->assertFalse(ProfileMenuLinkResource::canDeleteAny());
        $this->assertTrue(ProfileMenuLinkResource::canEdit($this->link(ProfileMenuKey::EMAIL)));
    }
}
