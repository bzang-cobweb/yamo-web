<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\RoleResource\Pages\CreateRole;
use Modules\Auth\Filament\Resources\RoleResource\Pages\EditRole;
use Modules\Auth\Filament\Resources\RoleResource\Pages\ListRoles;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\Role;

class RoleResourceTest extends AuthFilamentTestCase
{
    public function test_list_is_forbidden_without_view_permission(): void
    {
        $this->actingAsUserWith();

        Livewire::test(ListRoles::class)->assertForbidden();
    }

    public function test_list_shows_and_sorts_roles(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_ROLES);
        $roles = Role::all();

        Livewire::test(ListRoles::class)
            ->assertOk()
            ->assertCanSeeTableRecords($roles)
            ->sortTable('permissions_count')
            ->sortTable('users_count')
            ->searchTable('super')
            ->assertCountTableRecords(1);
    }

    public function test_creates_a_role_with_permissions(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_ROLES, PermissionEnum::CREATE_ROLES);
        $permission = Permission::findByName(PermissionEnum::VIEW_USERS->value);

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'editor', 'permissions' => [$permission->getKey()]])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Role::findByName('editor')->hasPermissionTo(PermissionEnum::VIEW_USERS));
    }

    public function test_create_is_forbidden_without_create_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_ROLES);

        Livewire::test(CreateRole::class)->assertForbidden();
    }

    public function test_edit_is_forbidden_without_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_ROLES);
        $role = Role::create(['name' => 'editor']);

        Livewire::test(EditRole::class, ['record' => $role->getKey()])->assertForbidden();
    }
}
