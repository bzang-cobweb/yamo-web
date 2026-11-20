<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Form\Models\FormUpload;
use Modules\Form\Tests\Concerns\MakesFormUploads;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferPhotoService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Le champ `upload` et l'étape « Photos » du formulaire de logement (décision
 * 2026-11-19-contrat-champ-upload-photos) : chaque photo est envoyée dès qu'elle
 * est choisie, validée selon le champ, puis rattachée à l'offre à l'enregistrement,
 * optimisée, dans l'ordre reçu ; au moins une photo.
 */
class OfferPhotoApiTest extends TestCase
{
    use MakesFormUploads, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CH', 'currency' => 'CHF']);
        $this->seed(OfferDatabaseSeeder::class);
        Storage::fake(FormUpload::DISK);
        Storage::fake('public');
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function input(array $overrides = []): array
    {
        return [
            'title' => 'Appartement lumineux',
            'price' => '1850',
            'street' => 'Rue du Lac 4',
            'city' => 'Genève',
            'status' => 'published',
            'property_type' => 'apartment',
            'rental_type' => 'long_term',
            'bedrooms' => '1',
            ...$overrides,
        ];
    }

    private function upload(User $user, UploadedFile $file, string $field = 'photos'): TestResponse
    {
        return $this->asUser($user)->post("/api/v1/forms/offer.offer/uploads/{$field}", ['file' => $file], ['Accept' => 'application/json']);
    }

    public function test_the_photos_field_serves_its_limits_declared_by_laravel(): void
    {
        $fields = array_column($this->asUser($this->member())->getJson('/api/v1/forms/offer.offer')->assertOk()->json('data.fields'), null, 'name');

        $this->assertSame('upload', $fields['photos']['type']);
        $this->assertTrue($fields['photos']['required']);
        // Libellé masqué : le titre d'étape suffit ; il sert encore aux messages (décision 2026-11-19-nuit-ordre-photos-libelle).
        $this->assertSame(['Photos', true], [$fields['photos']['label'], $fields['photos']['label_hidden']]);
        $this->assertFalse($fields['title']['label_hidden']);
        $this->assertSame([
            'accept' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size_kb' => OfferPhotoService::MAX_UPLOAD_KB,
            'max_files' => 20,
            'files' => [],
            'endpoint' => '/v1/forms/offer.offer/uploads/photos',
        ], $fields['photos']['upload']);
        $this->assertNull($fields['title']['upload']);
    }

    public function test_a_photo_is_sent_and_kept_for_its_author(): void
    {
        $user = $this->member();

        $id = $this->upload($user, UploadedFile::fake()->image('salon.jpg', 1200, 800))->assertCreated()->json('data.id');

        $upload = FormUpload::query()->where('uuid', $id)->sole();
        $this->assertSame($user->getKey(), $upload->user_id);
        $this->assertSame(['offer.offer', 'photos'], [$upload->form, $upload->field]);
        Storage::disk(FormUpload::DISK)->assertExists($upload->path);
    }

    public function test_a_file_outside_the_field_rules_is_refused(): void
    {
        $user = $this->member();

        $this->upload($user, UploadedFile::fake()->create('bail.pdf', 100, 'application/pdf'))->assertUnprocessable()->assertJsonValidationErrors(['file']);
        $this->upload($user, UploadedFile::fake()->image('enorme.jpg')->size(OfferPhotoService::MAX_UPLOAD_KB + 1))->assertUnprocessable()->assertJsonValidationErrors(['file']);
        // Un champ qui n'est pas un champ de fichiers, ou inconnu : 404.
        $this->upload($user, UploadedFile::fake()->image('a.jpg'), 'title')->assertNotFound();
        $this->upload($user, UploadedFile::fake()->image('a.jpg'), 'nope')->assertNotFound();
        $this->assertSame(0, FormUpload::query()->count());
    }

    public function test_a_guest_cannot_send_a_file(): void
    {
        $this->post('/api/v1/forms/offer.offer/uploads/photos', ['file' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    public function test_saving_attaches_the_photos_optimised_in_the_order_received(): void
    {
        $user = $this->member();
        $first = $this->pendingUpload($user, width: 3000, height: 2000);
        $second = $this->pendingUpload($user);

        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => [$second, $first]]))->assertNoContent();

        $media = Offer::query()->sole()->getMedia(Offer::PHOTOS);
        $this->assertSame([$second.'.webp', $first.'.webp'], $media->pluck('file_name')->all());
        $this->assertSame(['image/webp', 'image/webp'], $media->pluck('mime_type')->all());
        // 1600 px au plus sur le grand côté.
        [$width] = getimagesize($media[1]->getPath());
        $this->assertSame(1600, $width);
        // Les fichiers en attente sont retirés une fois utilisés.
        $this->assertSame(0, FormUpload::query()->count());
        $this->assertSame([], Storage::disk(FormUpload::DISK)->allFiles('form-uploads'));
    }

    public function test_at_least_one_photo_and_at_most_twenty(): void
    {
        $user = $this->member();

        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => []]))->assertUnprocessable()->assertJsonValidationErrors(['photos']);
        $this->asUser($user)->postJson('/api/v1/offers', $this->input())->assertUnprocessable()->assertJsonValidationErrors(['photos']);

        $many = array_map(fn (): string => $this->pendingUpload($user, width: 10, height: 10), range(1, 21));
        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => $many]))->assertUnprocessable()->assertJsonValidationErrors(['photos']);
        $this->assertSame(0, Offer::query()->count());
    }

    public function test_a_photo_of_another_account_or_expired_is_refused(): void
    {
        $user = $this->member();
        $theirs = $this->pendingUpload($this->member());
        $expired = $this->pendingUpload($user);
        FormUpload::query()->where('uuid', $expired)->update(['created_at' => now()->subHours(25)]);

        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => [$theirs]]))->assertUnprocessable()->assertJsonValidationErrors(['photos.0']);
        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => [$expired]]))->assertUnprocessable()->assertJsonValidationErrors(['photos.0']);
        $this->assertSame(0, Offer::query()->count());
    }

    public function test_editing_keeps_removes_and_reorders_the_photos(): void
    {
        $user = $this->member();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => [$this->pendingUpload($user), $this->pendingUpload($user)]]))->assertNoContent();
        $offer = Offer::query()->sole();
        [$kept, $removed] = $offer->getMedia(Offer::PHOTOS)->all();

        // Le formulaire de l'offre sert ses photos, et leurs identifiants comme valeur.
        $field = collect($this->asUser($user)->getJson("/api/v1/forms/offer.offer?record={$offer->getKey()}")->json('data.fields'))->firstWhere('name', 'photos');
        $this->assertSame([$kept->uuid, $removed->uuid], $field['default']);
        $this->assertSame([$kept->uuid, $removed->uuid], array_column($field['upload']['files'], 'id'));

        $new = $this->pendingUpload($user);
        $this->asUser($user)->patchJson("/api/v1/offers/{$offer->getKey()}", $this->input(['photos' => [$new, $kept->uuid]]))->assertNoContent();

        $this->assertSame([$new.'.webp', $kept->file_name], $offer->fresh()->getMedia(Offer::PHOTOS)->pluck('file_name')->all());
        $this->assertNull(Media::query()->find($removed->getKey()));
    }

    public function test_the_photo_of_another_offer_cannot_be_taken(): void
    {
        $user = $this->member();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => [$this->pendingUpload($user)]]))->assertNoContent();
        $this->asUser($user)->postJson('/api/v1/offers', $this->input(['photos' => [$this->pendingUpload($user)]]))->assertNoContent();
        [$mine, $other] = Offer::query()->orderBy('id')->get()->all();
        $foreign = $other->getFirstMedia(Offer::PHOTOS)->uuid;

        $this->asUser($user)->patchJson("/api/v1/offers/{$mine->getKey()}", $this->input(['photos' => [$foreign]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photos.0']);
    }

    public function test_unused_files_are_pruned_after_24_hours(): void
    {
        $user = $this->member();
        $old = $this->pendingUpload($user);
        $recent = $this->pendingUpload($user);
        FormUpload::query()->where('uuid', $old)->update(['created_at' => now()->subHours(25)]);
        $oldPath = FormUpload::query()->where('uuid', $old)->value('path');

        $this->artisan('model:prune', ['--model' => [FormUpload::class]])->assertSuccessful();

        $this->assertSame([$recent], FormUpload::query()->pluck('uuid')->all());
        Storage::disk(FormUpload::DISK)->assertMissing($oldPath);
    }
}
