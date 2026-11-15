<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Filament\Actions\Testing\TestAction;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Auth\Models\User;

/**
 * Supprimer un compte révoque ses jetons d'API, quel que soit le chemin.
 */
class UserDeletionTokensTest extends AuthFilamentTestCase
{
    private function userWithTokens(): User
    {
        $user = User::factory()->create();
        $user->createToken('iPhone');
        $user->createToken('iPad');

        return $user;
    }

    private function tokensOf(User $user): int
    {
        return PersonalAccessToken::where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->getKey())
            ->count();
    }

    public function test_deleting_a_user_in_code_revokes_its_tokens(): void
    {
        $user = $this->userWithTokens();
        $other = $this->userWithTokens();

        $user->delete();

        $this->assertSame(0, $this->tokensOf($user));
        $this->assertSame(2, $this->tokensOf($other));
    }

    public function test_deleting_from_the_edit_page_revokes_its_tokens(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS, PermissionEnum::DELETE_USERS);
        $user = $this->userWithTokens();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->callAction(TestAction::make('delete'));

        $this->assertModelMissing($user);
        $this->assertSame(0, $this->tokensOf($user));
    }

    public function test_bulk_delete_revokes_the_tokens_of_each_user(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::DELETE_USERS);
        $first = $this->userWithTokens();
        $second = $this->userWithTokens();

        Livewire::test(ListUsers::class)
            ->selectTableRecords([$first, $second])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertSame(0, $this->tokensOf($first) + $this->tokensOf($second));
    }
}
