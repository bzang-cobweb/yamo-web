<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;

class UserResourceTest extends AuthFilamentTestCase
{
    public function test_list_is_forbidden_without_view_permission(): void
    {
        $this->actingAsUserWith();

        Livewire::test(ListUsers::class)->assertForbidden();
    }

    public function test_list_shows_searches_and_sorts_users(): void
    {
        $admin = $this->actingAsUserWith(PermissionEnum::VIEW_USERS);
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users->push($admin))
            ->sortTable('name')
            ->assertCanSeeTableRecords($users->sortBy('name'), inOrder: true)
            ->sortTable('email')
            ->assertCanSeeTableRecords($users->sortBy('email'), inOrder: true)
            ->searchTable($users->first()->email)
            ->assertCountTableRecords(1);
    }

    public function test_super_admin_sees_the_list_without_explicit_permission(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ListUsers::class)->assertOk();
    }

    public function test_create_is_forbidden_without_create_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS);

        Livewire::test(CreateUser::class)->assertForbidden();
    }

    public function test_creates_a_user_with_a_role(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);
        $role = Role::create(['name' => 'editor']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Jeanne',
                'email' => 'jeanne@example.com',
                'password' => 'secret-password',
                'roles' => [$role->getKey()],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::firstWhere('email', 'jeanne@example.com');
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => '', 'email' => '', 'password' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'required', 'password' => 'required']);
    }

    public function test_edit_keeps_password_when_left_empty(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $user = User::factory()->create();
        $hash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['name' => 'Nouveau nom', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Nouveau nom', $user->name);
        $this->assertSame($hash, $user->password);
    }

    public function test_edit_is_forbidden_without_update_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS);
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])->assertForbidden();
    }

    public function test_deletes_another_user_but_not_itself(): void
    {
        $admin = $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::DELETE_USERS);
        $other = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($admin))
            ->callAction(TestAction::make('delete')->table($other));

        $this->assertModelMissing($other);
        $this->assertModelExists($admin);
    }

    public function test_super_admin_cannot_delete_itself(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $other = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($admin))
            ->assertActionVisible(TestAction::make('delete')->table($other));
    }

    public function test_delete_is_hidden_without_delete_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS);
        $other = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($other));
    }
}
