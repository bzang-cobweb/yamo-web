<?php

namespace Modules\Auth\Tests\Feature\Api;

use Bza\Form\Models\FormUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Enums\IdentityDocumentType;
use Modules\Auth\Models\User;
use Modules\Auth\Services\IdentityDocumentService;
use Modules\Location\Models\Country;
use Tests\Concerns\MakesFormUploads;
use Tests\TestCase;

/**
 * « S'identifier » (décision 2026-11-25-identification) : le compte envoie une pièce d'identité, gardée sur le disque
 * privé ; l'équipe remplit la date d'identification au back-office.
 */
class IdentityApiTest extends TestCase
{
    use MakesFormUploads, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(FormUpload::DISK);
        $this->country = Country::factory()->create(['code' => 'CH', 'name' => ['fr' => 'Suisse', 'en' => 'Switzerland'], 'native_name' => 'Schweiz']);
    }

    private Country $country;

    /**
     * Le pays et le type de la pièce, demandés avant les photos (décision 2026-11-27-identite-pays-et-type-de-piece).
     *
     * @return array{identity_country_id: string, identity_document_type: string}
     */
    private function piece(): array
    {
        return ['identity_country_id' => (string) $this->country->getKey(), 'identity_document_type' => 'passport'];
    }

    public function test_the_form_starts_with_a_note_the_country_and_the_document_type_then_saves_them(): void
    {
        $user = User::factory()->create(['locale' => 'fr', 'country_id' => $this->country->getKey()]);
        $fields = $this->asUser($user)->getJson('/api/v1/forms/auth.identity')->assertOk()->json('data.fields');

        $this->assertSame(['identity_country_id', 'identity_document_type', 'documents'], array_column($fields, 'name'));
        $this->assertSame('Nous ne partageons pas ces informations.', $fields[0]['notice']);
        $this->assertSame('select', $fields[0]['type']);
        // Le pays du compte d'office.
        $this->assertSame((string) $this->country->getKey(), $fields[0]['default']);
        $this->assertSame('radio', $fields[1]['type']);
        $this->assertSame(['driving_licence', 'passport', 'identity_card'], array_column($fields[1]['options'], 'value'));
        $this->assertNull($fields[1]['default']);

        $this->asUser($user)->postJson('/api/v1/auth/me/identity', [
            'identity_country_id' => (string) $this->country->getKey(),
            'identity_document_type' => 'driving_licence',
            'documents' => [$this->pendingUpload($user, 'documents', 'auth.identity')],
        ])->assertNoContent();

        $user->refresh();
        $this->assertSame($this->country->getKey(), $user->identity_country_id);
        $this->assertSame(IdentityDocumentType::DRIVING_LICENCE, $user->identity_document_type);
        $this->assertSame('driving_licence', $this->asUser($user)->getJson('/api/v1/forms/auth.identity')->json('data.fields.1.default'));
    }

    public function test_the_country_and_the_document_type_are_required_and_checked(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->asUser($user)->postJson('/api/v1/auth/me/identity', ['documents' => [$this->pendingUpload($user, 'documents', 'auth.identity')]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identity_country_id', 'identity_document_type']);
        $this->asUser($user)->postJson('/api/v1/auth/me/identity', ['identity_country_id' => '999999', 'identity_document_type' => 'library_card', 'documents' => [$this->pendingUpload($user, 'documents', 'auth.identity')]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identity_country_id', 'identity_document_type']);
        $this->assertNull($user->fresh()->identity_document_type);
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    public function test_the_personal_submenu_links_to_identity(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        // Dans « Informations personnelles » (décision 2026-11-27-menu-profil-icones-sous-menus).
        $menu = $this->asUser($user)->getJson('/api/v1/auth/me/menu/personal')->assertOk()->json('data');
        $keys = array_column($menu, 'key');

        $this->assertSame("S'identifier", $menu[array_search('identity', $keys, true)]['label']);
        $this->assertSame('auth.identity', $menu[array_search('identity', $keys, true)]['form']);
    }

    public function test_the_form_asks_for_a_photo_and_its_note_tells_the_state(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $form = fn (): array => $this->asUser($user)->getJson('/api/v1/forms/auth.identity')->assertOk()->json('data');

        $this->assertSame('upload', $form()['fields'][2]['type']);
        $this->assertStringStartsWith('Envoyez une pièce', $form()['notice']);

        $this->asUser($user)->postJson('/api/v1/auth/me/identity', [...$this->piece(), 'documents' => [$this->pendingUpload($user, 'documents', 'auth.identity')]])
            ->assertNoContent();
        $this->assertSame("Votre pièce d'identité est en cours de vérification par l'équipe.", $form()['notice']);

        $user->forceFill(['identified_at' => '2026-11-20 10:00:00'])->save();
        $this->assertSame('Votre compte est identifié depuis le 20 novembre 2026. Les photos validées restent ; vous pouvez en ajouter.', $form()['notice']);
    }

    public function test_documents_are_kept_privately_shown_by_a_signed_link_and_removable_before_validation(): void
    {
        $user = User::factory()->create();
        $send = fn (array $ids) => $this->asUser($user)->postJson('/api/v1/auth/me/identity', [...$this->piece(), 'documents' => $ids])->assertNoContent();

        $send([$this->pendingUpload($user, 'documents', 'auth.identity'), $this->pendingUpload($user, 'documents', 'auth.identity')]);
        $documents = $user->fresh()->getMedia(IdentityDocumentService::COLLECTION);
        $this->assertCount(2, $documents);
        $this->assertSame(IdentityDocumentService::DISK, $documents->first()->disk);
        Storage::disk(IdentityDocumentService::DISK)->assertExists($documents->first()->getPathRelativeToRoot());

        // Le champ les montre, par un lien signé ; sans signature, rien (décision 2026-11-25-identite-plusieurs-photos).
        $field = $this->asUser($user)->getJson('/api/v1/forms/auth.identity')->assertOk()->json('data.fields.2');
        $this->assertSame($documents->pluck('uuid')->all(), $field['default']);
        $this->assertSame(IdentityDocumentService::MAX_FILES, $field['upload']['max_files']);
        $url = $field['upload']['files'][0]['url'];
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk();
        $this->get(strtok($url, '?'))->assertForbidden();

        // Pas encore identifié : garder l'une, en ajouter une, l'autre est retirée.
        $kept = (string) $documents->first()->uuid;
        $send([$kept, $this->pendingUpload($user, 'documents', 'auth.identity')]);
        $after = $user->fresh()->getMedia(IdentityDocumentService::COLLECTION);
        $this->assertCount(2, $after);
        $this->assertContains($kept, $after->pluck('uuid')->all());
    }

    public function test_once_identified_the_validated_documents_stay_and_new_ones_are_added(): void
    {
        $user = User::factory()->create();
        $this->asUser($user)->postJson('/api/v1/auth/me/identity', [...$this->piece(), 'documents' => [$this->pendingUpload($user, 'documents', 'auth.identity')]])->assertNoContent();
        $user->forceFill(['identified_at' => now()])->save();

        $this->asUser($user)->postJson('/api/v1/auth/me/identity', [...$this->piece(), 'documents' => [$this->pendingUpload($user, 'documents', 'auth.identity')]])->assertNoContent();

        $this->assertCount(2, $user->fresh()->getMedia(IdentityDocumentService::COLLECTION));
        $this->assertTrue($user->fresh()->isIdentified());
    }

    public function test_a_document_is_required_and_must_be_the_accounts_own(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->asUser($user)->postJson('/api/v1/auth/me/identity', [...$this->piece(), 'documents' => []])->assertUnprocessable()->assertJsonValidationErrors('documents');
        $this->asUser($user)->postJson('/api/v1/auth/me/identity', [...$this->piece(), 'documents' => [$this->pendingUpload($other, 'documents', 'auth.identity')]])
            ->assertUnprocessable();
        $this->assertCount(0, $user->fresh()->getMedia(IdentityDocumentService::COLLECTION));
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/me/identity', [])->assertUnauthorized();
    }
}
