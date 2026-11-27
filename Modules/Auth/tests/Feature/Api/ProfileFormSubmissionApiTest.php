<?php

namespace Modules\Auth\Tests\Feature\Api;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\FormRegistry;
use Bza\Form\Support\ListingRegistry;
use Bza\Form\Support\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Enums\ProfileMenuKey;
use Modules\Auth\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Les formulaires du profil décrivent leur envoi, que l'app exécute sans les
 * connaître d'avance (décision 2026-11-17-formulaires-profil-generiques).
 */
class ProfileFormSubmissionApiTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(User $user): static
    {
        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    /**
     * @return array<string, array{string, string, string, bool, string, string}>
     */
    public static function profileForms(): array
    {
        return [
            'informations personnelles' => ['auth.profile.personal', 'PATCH', '/v1/auth/me/personal-info', false, 'profile', 'Modifications enregistrées.'],
            'e-mail' => ['auth.profile.email', 'POST', '/v1/auth/me/email', false, 'profile', 'Adresse e-mail enregistrée.'],
            'téléphone' => ['auth.profile.phone', 'POST', '/v1/auth/me/phone', false, 'profile', 'Numéro enregistré.'],
            'mot de passe' => ['auth.profile.password', 'PUT', '/v1/auth/me/password', false, 'profile', 'Mot de passe modifié.'],
            'préférences' => ['auth.profile.preferences', 'PATCH', '/v1/auth/me/preferences', false, 'profile', 'Modifications enregistrées.'],
            'suppression du compte' => ['auth.profile.delete-account', 'DELETE', '/v1/auth/me', true, 'sign-out', 'Votre compte a été supprimé.'],
        ];
    }

    #[DataProvider('profileForms')]
    public function test_a_profile_form_describes_its_submission(string $form, string $method, string $endpoint, bool $destructive, string $action, string $message): void
    {
        $this->asUser(User::factory()->create(['locale' => 'fr']))
            ->getJson("/api/v1/forms/{$form}")
            ->assertOk()
            ->assertJsonPath('data.submit.method', $method)
            ->assertJsonPath('data.submit.endpoint', $endpoint)
            ->assertJsonPath('data.submit.destructive', $destructive)
            ->assertJsonPath('data.submit.success', ['action' => $action, 'message' => $message]);
    }

    public function test_every_menu_link_opens_a_form_sent_to_an_existing_api_route_or_a_list(): void
    {
        $user = User::factory()->create();
        $registry = app(FormRegistry::class);

        foreach (ProfileMenuKey::cases() as $key) {
            // Un sous-menu n'ouvre ni formulaire ni liste (décision 2026-11-27-menu-profil-icones-sous-menus).
            if ($key->menu() !== null) {
                $this->assertNull($key->form(), "{$key->value} : un sous-menu n'ouvre pas de formulaire.");

                continue;
            }

            if ($key->list() !== null) {
                $this->assertNull($key->form(), "{$key->value} : un lien ouvre un formulaire ou une liste, pas les deux.");
                $this->assertTrue(app(ListingRegistry::class)->has($key->list()), "{$key->value} : liste inconnue.");

                continue;
            }

            $submission = $registry->make((string) $key->form(), $user)?->submission();

            $this->assertNotNull($submission, "{$key->value} : formulaire inconnu ou sans envoi décrit.");
            $route = Route::getRoutes()->match(request()->create('/api'.$submission->endpoint, $submission->method));
            $this->assertStringStartsWith('api.v1.', (string) $route->getName());
        }
    }

    public function test_a_form_the_app_already_knows_keeps_its_label_only(): void
    {
        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.login')
            ->assertOk()
            ->assertJsonMissingPath('data.submit.endpoint')
            ->assertJsonPath('data.submit', ['label' => 'Se connecter']);
    }

    public function test_the_served_submission_saves_the_form(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $submit = $this->asUser($user)->getJson('/api/v1/forms/auth.profile.preferences')->json('data.submit');

        $this->asUser($user)->json($submit['method'], '/api'.$submit['endpoint'], ['locale' => 'en'])->assertOk();

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_a_new_form_and_route_are_served_without_anything_else(): void
    {
        // Un formulaire et sa route, déclarés hors du module : rien d'autre à changer
        // pour que l'API le décrive à l'app, envoi compris.
        Route::middleware(['api', 'auth:sanctum'])->prefix('api')->name('api.')
            ->post('v1/test/nickname', fn () => response()->noContent())->name('v1.test.nickname');
        Route::getRoutes()->refreshNameLookups();
        app(FormRegistry::class)->register(NicknameForm::class);

        $user = User::factory()->create(['locale' => 'fr']);
        $submit = $this->asUser($user)->getJson('/api/v1/forms/test.nickname')
            ->assertOk()
            ->assertJsonPath('data.title', 'Surnom')
            ->json('data.submit');

        $this->assertSame(['label' => 'OK', 'method' => 'POST', 'endpoint' => '/v1/test/nickname', 'destructive' => false, 'success' => ['action' => 'profile', 'message' => 'Surnom enregistré.']], $submit);
        $this->asUser($user)->json($submit['method'], '/api'.$submit['endpoint'], ['nickname' => 'Jo'])->assertNoContent();
    }
}

/** Formulaire de test : un lien que le menu pourrait ajouter. */
class NicknameForm extends Form
{
    public static function name(): string
    {
        return 'test.nickname';
    }

    public function fields(): array
    {
        return [Field::text('nickname')->label('Surnom')->required()];
    }

    public function title(): string
    {
        return 'Surnom';
    }

    public function submitLabel(): string
    {
        return 'OK';
    }

    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.test.nickname', SuccessAction::PROFILE, 'Surnom enregistré.');
    }
}
