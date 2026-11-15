<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\PermissionResource\Pages\ListPermissions;
use Modules\Auth\Filament\Resources\PermissionResource\Pages\ViewPermission;
use Modules\Auth\Models\Permission;

class PermissionResourceTest extends AuthFilamentTestCase
{
    public function test_list_is_forbidden_without_view_permission(): void
    {
        $this->actingAsUserWith();

        Livewire::test(ListPermissions::class)->assertForbidden();
    }

    public function test_list_shows_every_declared_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_PERMISSIONS);

        Livewire::test(ListPermissions::class)
            ->assertOk()
            ->assertCountTableRecords(count(PermissionEnum::cases()))
            ->sortTable('roles_count')
            ->searchTable('users.')
            ->assertCountTableRecords(4);
    }

    public function test_view_shows_the_translated_label(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_PERMISSIONS);
        $permission = Permission::findByName(PermissionEnum::VIEW_USERS->value);

        Livewire::test(ViewPermission::class, ['record' => $permission->getKey()])
            ->assertOk()
            ->assertSee(__('auth::permission.names.users.view'));
    }
}
