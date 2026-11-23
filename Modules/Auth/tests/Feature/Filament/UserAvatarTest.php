<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AvatarService;

/**
 * Photo de profil dans le back-office : même optimisation que l'API.
 */
class UserAvatarTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * En mode avatar, Filament recadre en carré dans le navigateur, puis refuse un
     * fichier qui ne l'est pas : le test envoie donc une image carrée.
     */
    public function test_an_admin_sets_a_user_photo_which_is_optimised(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['avatar' => UploadedFile::fake()->image('portrait.jpg', 1600, 1600)])
            ->call('save')
            ->assertHasNoFormErrors();

        $media = $user->fresh()->getFirstMedia(AvatarService::COLLECTION);
        $this->assertNotNull($media);
        $this->assertSame('image/webp', $media->mime_type);
        [$width, $height] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot()));
        // L'original, plafonné à 1024 px (décision 2026-11-24-partenaire-logo-sans-original).
        $this->assertSame([1024, 1024], [$width, $height]);
        $this->assertCount(1, Storage::disk('public')->files('auth/avatar/'.$media->uuid));
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_USERS, PermissionEnum::UPDATE_USERS);
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['avatar' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasFormErrors(['avatar']);

        $this->assertNull($user->fresh()->getFirstMedia(AvatarService::COLLECTION));
    }

    public function test_the_list_and_the_top_bar_show_the_photo(): void
    {
        $admin = $this->actingAsUserWith(PermissionEnum::VIEW_USERS);
        $photo = UploadedFile::fake()->image('a.jpg', 300, 300);
        app(AvatarService::class)->store($admin, $photo->getRealPath());

        $url = $admin->fresh()->avatarUrl('admin');
        $this->assertStringContainsString('/conversions/avatar-admin.webp', (string) $url);
        $this->assertStringEndsWith((string) $url, Filament::getUserAvatarUrl($admin->fresh()));

        // Sur le disque factice, la colonne sert une adresse signée sans `/storage` :
        // on vérifie le chemin de la vignette sur le disque.
        $thumb = $admin->fresh()->getFirstMedia(AvatarService::COLLECTION)->getPathRelativeToRoot('admin');
        $this->assertStringContainsString($thumb, Livewire::test(ListUsers::class)->html());
    }

    public function test_without_photo_the_top_bar_keeps_its_default(): void
    {
        $admin = $this->actingAsUserWith(PermissionEnum::VIEW_USERS);

        $this->assertNull($admin->avatarUrl());
        $this->assertNotEmpty(Filament::getUserAvatarUrl($admin));
    }
}
