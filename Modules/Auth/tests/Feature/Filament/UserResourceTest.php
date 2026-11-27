<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Bza\Form\Support\Locales;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;

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
        $admin->update(['name' => 'mmm', 'first_name' => 'Mmm', 'last_name' => 'Mmm', 'email' => 'mmm@example.com']);
        // Valeurs distinctes et sans accent : un ex æquo rendrait l'ordre attendu indéterminé.
        $users = User::factory()->forEachSequence(
            ['name' => 'zoe', 'first_name' => 'Anne', 'last_name' => 'Zola', 'email' => 'b@example.com'],
            ['name' => 'alix', 'first_name' => 'Zelie', 'last_name' => 'Brun', 'email' => 'z@example.com'],
            ['name' => 'paul', 'first_name' => 'Paul', 'last_name' => 'Adam', 'email' => 'a@example.com'],
        )->create();

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users->push($admin))
            ->sortTable('name')
            ->assertCanSeeTableRecords($users->sortBy('name'), inOrder: true)
            ->sortTable('first_name')
            ->assertCanSeeTableRecords($users->sortBy('first_name'), inOrder: true)
            ->sortTable('last_name')
            ->assertCanSeeTableRecords($users->sortBy('last_name'), inOrder: true)
            ->sortTable('locale')
            ->sortTable('email')
            ->assertCanSeeTableRecords($users->sortBy('email'), inOrder: true)
            ->searchTable($users->first()->email)
            ->assertCountTableRecords(1)
            ->searchTable($users->first()->last_name)
            ->assertCanSeeTableRecords([$users->first()])
            ->searchTable($users->first()->name)
            ->assertCanSeeTableRecords([$users->first()]);
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
                'name' => 'jeanne',
                'first_name' => 'Jeanne',
                'last_name' => 'Dupont',
                'gender' => 'female',
                'birth_date' => '1990-04-12',
                'email' => 'jeanne@example.com',
                'locale' => 'en',
                'password' => 'secret-password',
                'roles' => [$role->getKey()],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::firstWhere('email', 'jeanne@example.com');
        $this->assertSame('female', $user->gender->value);
        $this->assertSame('1990-04-12', $user->birth_date->format('Y-m-d'));
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->hasRole('editor'));
        $this->assertSame('jeanne', $user->name);
        $this->assertSame('Jeanne Dupont', $user->full_name);
        $this->assertSame('en', $user->preferredLocale());
    }

    public function test_locale_defaults_to_the_application_locale(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);

        Livewire::test(CreateUser::class)
            ->assertSchemaStateSet(['locale' => Locales::default()]);
    }

    public function test_create_rejects_an_unsupported_locale(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'jeanne',
                'first_name' => 'Jeanne',
                'last_name' => 'Dupont',
                'email' => 'jeanne@example.com',
                'password' => 'secret-password',
                'locale' => 'de',
            ])
            ->call('create')
            ->assertHasFormErrors(['locale' => 'in']);

        $this->assertDatabaseMissing(User::class, ['email' => 'jeanne@example.com']);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'password' => ''])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                // Ni e-mail ni téléphone : l'un des deux est exigé.
                'email' => 'required_without',
                'phone' => 'required_without',
                'password' => 'required',
            ]);
    }

    public function test_pseudonym_must_be_unique(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);
        User::factory()->create(['name' => 'jeanne']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'jeanne',
                'first_name' => 'Jeanne',
                'last_name' => 'Dupont',
                'email' => 'jeanne@example.com',
                'password' => 'secret-password',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_edit_keeps_password_when_left_empty(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $user = User::factory()->create();
        $hash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['first_name' => 'Nouveau', 'last_name' => 'Nom', 'locale' => 'en', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Nouveau Nom', $user->full_name);
        $this->assertSame('en', $user->locale);
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

        $this->assertSoftDeleted($other);
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

    public function test_bulk_delete_never_deletes_its_own_account(): void
    {
        $admin = $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::DELETE_USERS);
        $other = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->selectTableRecords([$admin, $other])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertModelExists($admin);
        $this->assertSoftDeleted($other);
    }

    public function test_gender_is_required_and_birth_date_bounded_in_the_back_office(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'paul', 'first_name' => 'Paul', 'last_name' => 'Martin', 'email' => 'paul@example.com',
                'locale' => 'fr', 'password' => 'secret-password', 'gender' => null,
                'birth_date' => now()->addDay()->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasFormErrors(['gender' => 'required', 'birth_date']);

        $this->assertNull(User::firstWhere('email', 'paul@example.com'));
    }

    public function test_a_user_created_with_a_phone_only_is_stored_in_e164(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::CREATE_USERS);
        $switzerland = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'paul', 'first_name' => 'Paul', 'last_name' => 'Martin', 'gender' => 'male',
                'locale' => 'fr', 'password' => 'secret-password',
                'phone_country_id' => $switzerland->getKey(), 'phone' => '079 123 45 67',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::firstWhere('name', 'paul');
        $this->assertNull($user->email);
        $this->assertSame('+41791234567', $user->phone);
    }

    public function test_the_list_shows_who_is_online(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS);
        $online = User::factory()->create(['last_seen_at' => now()]);
        $offline = User::factory()->create(['last_seen_at' => now()->subHour()]);

        Livewire::test(ListUsers::class)
            ->assertTableColumnStateSet('is_online', true, $online)
            ->assertTableColumnStateSet('is_online', false, $offline);
    }

    public function test_restores_an_account_deleted_by_its_owner(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::DELETE_USERS);
        $deleted = User::factory()->create();
        $deleted->delete();

        Livewire::test(ListUsers::class)
            ->assertCanNotSeeTableRecords([$deleted])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$deleted])
            ->callAction(TestAction::make('restore')->table($deleted));

        $this->assertNotSoftDeleted($deleted);
    }

    public function test_restore_is_hidden_without_delete_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS);
        $deleted = User::factory()->create();
        $deleted->delete();

        Livewire::test(ListUsers::class)
            ->filterTable('trashed', true)
            ->assertActionHidden(TestAction::make('restore')->table($deleted));
    }
}
