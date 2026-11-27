<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\RelationManagers\PhonesRelationManager;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;

/**
 * Les numéros supplémentaires d'un utilisateur sur sa fiche (décision 2026-11-27-plusieurs-numeros) : listés en entier,
 * retirables ; aucun ajout depuis le back-office (il passe par un code SMS).
 */
class UserPhonesRelationManagerTest extends AuthFilamentTestCase
{
    private function manager(User $user)
    {
        return Livewire::test(PhonesRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => EditUser::class,
        ]);
    }

    public function test_lists_only_the_user_extra_numbers_and_removes_one(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $user = User::factory()->create();
        $phone = $user->phones()->create(['phone' => '+41782223344', 'verified_at' => now()]);
        $other = User::factory()->create()->phones()->create(['phone' => '+41763334455', 'verified_at' => now()]);

        $this->manager($user)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$phone])
            ->assertCanNotSeeTableRecords([$other])
            ->assertActionDoesNotExist(TestAction::make('create')->table())
            ->callAction(TestAction::make('delete')->table($phone));

        $this->assertModelMissing($phone);
        $this->assertSame(1, UserPhone::query()->count());
    }
}
