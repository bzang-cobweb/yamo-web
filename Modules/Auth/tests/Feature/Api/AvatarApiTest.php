<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AvatarService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Photo de profil depuis l'app mobile : `POST` et `DELETE /api/v1/auth/me/avatar`.
 */
class AvatarApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function upload(User $user, UploadedFile $file): TestResponse
    {
        return $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('Accept-Language', 'fr')
            ->post('/api/v1/auth/me/avatar', ['avatar' => $file], ['Accept' => 'application/json']);
    }

    private function avatarOf(User $user): ?Media
    {
        return $user->fresh()->getFirstMedia(AvatarService::COLLECTION);
    }

    public function test_an_uploaded_photo_is_optimised_before_being_stored(): void
    {
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('vacances.jpg', 3000, 2000);

        $response = $this->upload($user, $photo)->assertOk();

        $media = $this->avatarOf($user);
        $this->assertNotNull($media);
        $disk = Storage::disk('public');

        // L'original est gardé, recadré en carré WebP et plafonné à 720 px (`AUTH_AVATAR_ORIGINAL_SIZE`, décision
        // 2026-11-24-avatar-list-detail), et ses conversions.
        $this->assertSame('avatar.webp', $media->file_name);
        $this->assertSame('image/webp', $media->mime_type);
        [$width, $height] = getimagesize($disk->path($media->getPathRelativeToRoot()));
        $this->assertSame([720, 720], [$width, $height]);
        $this->assertCount(1, $disk->files('auth/avatar/'.$media->uuid));

        // Une conversion carrée par écran, produite tout de suite, à la taille de la configuration
        // (décision 2026-11-24-avatar-photos-offre-conversions).
        $sizes = [];
        foreach (['list', 'detail'] as $name) {
            $sizes[$name] = getimagesize($disk->path($media->getPathRelativeToRoot($name)));
            $sizes[$name] = [$sizes[$name][0], $sizes[$name][1]];
        }
        $this->assertSame(['list' => [144, 144], 'detail' => [288, 288]], $sizes);

        // Adresse sur l'hôte de la requête, rangée sous <module>/<champ>/<uuid>, sans numéro séquentiel.
        $this->assertStringEndsWith(
            '/storage/auth/avatar/'.$media->uuid.'/conversions/avatar-detail.webp',
            $response->json('data.avatar_url'),
        );
        $this->assertStringNotContainsString('/storage/'.$media->getKey().'/', $response->json('data.avatar_url'));
    }

    public function test_a_photo_smaller_than_the_stored_size_is_refused(): void
    {
        // Au moins la taille enregistrée (l'original, 720 px) sur le plus grand côté : il peut s'ouvrir en grand
        // (décision 2026-11-24-image-taille-minimale-enregistree).
        $user = User::factory()->create();

        $this->upload($user, UploadedFile::fake()->image('petite.png', 719, 719))->assertUnprocessable()->assertJsonValidationErrors(['avatar']);
        $this->assertNull($this->avatarOf($user));
        $this->upload($user, UploadedFile::fake()->image('juste.png', 720, 500))->assertOk();
    }

    public function test_a_small_photo_is_cropped_without_being_enlarged(): void
    {
        // Recadrer ne doit jamais agrandir : 400 × 300 donne un carré de 300 (décision 2026-11-24-partenaire-logo-sans-original) ;
        // l'envoi le refuse, l'enregistrement direct (back-office, démo) reste protégé.
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('petite.jpg', 400, 300);
        app(AvatarService::class)->store($user, $photo->getRealPath());

        [$width, $height] = getimagesize(Storage::disk('public')->path($this->avatarOf($user)->getPathRelativeToRoot()));
        $this->assertSame([300, 300], [$width, $height]);
    }

    public function test_the_avatar_url_follows_the_requested_host(): void
    {
        $user = User::factory()->create();
        $this->upload($user, UploadedFile::fake()->image('a.png', 800, 800))->assertOk();
        $uuid = $this->avatarOf($user)->uuid;

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('http://yamo.ddev.site/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.avatar_url', 'http://yamo.ddev.site/storage/auth/avatar/'.$uuid.'/conversions/avatar-detail.webp');
    }

    public function test_a_new_photo_replaces_the_previous_one(): void
    {
        $user = User::factory()->create();
        $this->upload($user, UploadedFile::fake()->image('first.jpg', 800, 800))->assertOk();
        $first = $this->avatarOf($user);

        $this->upload($user, UploadedFile::fake()->image('second.png', 900, 900))->assertOk();

        $second = $this->avatarOf($user);
        $this->assertNotSame($first->uuid, $second->uuid);
        $this->assertSame(1, $user->fresh()->getMedia(AvatarService::COLLECTION)->count());
        $this->assertSame(['auth/avatar/'.$second->uuid], Storage::disk('public')->directories('auth/avatar'));
    }

    public function test_removing_the_photo_deletes_its_files(): void
    {
        $user = User::factory()->create();
        $this->upload($user, UploadedFile::fake()->image('a.jpg', 800, 800))->assertOk();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/auth/me/avatar')
            ->assertOk()
            ->assertJsonPath('data.avatar_url', null);

        $this->assertNull($this->avatarOf($user));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_file_that_is_not_an_image_is_refused_without_touching_the_current_photo(): void
    {
        $user = User::factory()->create();
        $this->upload($user, UploadedFile::fake()->image('a.jpg', 800, 800))->assertOk();
        $current = $this->avatarOf($user)->uuid;

        $this->upload($user, UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar'])
            ->assertJsonCount(1, 'errors.avatar');

        $this->assertSame($current, $this->avatarOf($user)->uuid);
    }

    public function test_a_photo_over_5_mb_is_refused_with_a_translated_message(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $response = $this->upload($user, UploadedFile::fake()->image('big.jpg', 800, 800)->size(5121))
            ->assertUnprocessable();

        $this->assertStringContainsString('photo de profil', $response->json('errors.avatar.0'));
        $this->assertNull($this->avatarOf($user));
    }

    public function test_a_guest_cannot_change_a_photo(): void
    {
        $this->post('/api/v1/auth/me/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
            ->assertUnauthorized();
        $this->deleteJson('/api/v1/auth/me/avatar')->assertUnauthorized();
    }

    public function test_the_photo_stays_until_the_account_is_purged(): void
    {
        $user = User::factory()->create();
        $this->upload($user, UploadedFile::fake()->image('a.jpg', 800, 800))->assertOk();

        // Suppression récupérable : la photo reste, pour une restauration.
        $user->fresh()->delete();
        $this->assertSame(1, Media::count());

        // Purge définitive : la photo part avec le compte.
        $user->fresh()->forceDelete();

        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
