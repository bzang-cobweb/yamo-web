<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Auth\Enums\IdentityDocumentType;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Models\User;
use Modules\Auth\Services\IdentityDocumentService;
use Modules\Location\Models\Country;

/**
 * L'identification au back-office (décision 2026-11-25-identification) : l'équipe ouvre les pièces d'identité par un
 * lien signé, puis « Valider l'identité » remplit la date ; « Retirer l'identification » la vide.
 */
class UserIdentityTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(IdentityDocumentService::DISK);
    }

    public function test_the_team_sees_the_documents_and_validates_then_revokes_the_identity(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('carte.jpg', 800, 500))
            ->toMediaCollection(IdentityDocumentService::COLLECTION, IdentityDocumentService::DISK);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->assertSee('carte.jpg')
            ->assertActionVisible('validateIdentity')
            ->assertActionHidden('revokeIdentity')
            ->callAction('validateIdentity', ['identified_at' => '2026-11-24 15:30:00'])
            ->assertHasNoActionErrors();

        $this->assertSame('2026-11-24 15:30:00', $user->fresh()->identified_at?->format('Y-m-d H:i:s'));

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->assertActionHidden('validateIdentity')
            ->callAction('revokeIdentity');

        $this->assertFalse($user->fresh()->isIdentified());
    }

    public function test_the_team_sees_the_issuing_country_and_the_document_type(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $country = Country::factory()->create(['code' => 'CH', 'name' => ['fr' => 'Suisse', 'en' => 'Switzerland'], 'native_name' => 'Suisse']);
        $user = User::factory()->create();
        $user->forceFill(['identity_country_id' => $country->getKey(), 'identity_document_type' => IdentityDocumentType::PASSPORT])->save();

        // Décision 2026-11-27-identite-pays-et-type-de-piece.
        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->assertSee('Pays de délivrance')
            ->assertSee('Suisse')
            ->assertSee('Passeport');
    }

    public function test_without_the_update_permission_the_identity_cannot_be_validated(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS);
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])->assertForbidden();
        $this->assertFalse($user->fresh()->isIdentified());
    }
}
