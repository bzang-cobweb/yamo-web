<?php

namespace Tests\Feature;

use App\Support\EnvFile;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use InvalidArgumentException;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Tests\TestCase;

/**
 * `project:init` (décision 2026-11-18-initialisation-par-domaine) : un projet
 * opérationnel pour un domaine, sans jamais toucher aux secrets du `.env`.
 */
class ProjectInitCommandTest extends TestCase
{
    use RefreshDatabase;

    private const ENV = "APP_NAME=Ancien\nAPP_KEY=base64:secret-key-not-to-touch\nDB_PASSWORD=\"s3cret pass\"\nAPP_LOCALE=en\n";

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/project-init-'.uniqid();
        mkdir($this->directory);
        file_put_contents($this->directory.'/.env', self::ENV);
        $this->app->useEnvironmentPath($this->directory);
    }

    protected function tearDown(): void
    {
        @unlink($this->directory.'/.env');
        @rmdir($this->directory);

        parent::tearDown();
    }

    private function env(): string
    {
        return (string) file_get_contents($this->directory.'/.env');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function init(array $overrides = []): PendingCommand
    {
        return $this->artisan('project:init', [
            '--domain' => 'rental',
            '--name' => 'Mon Projet',
            '--locale' => 'fr',
            '--country' => ['CH'],
            '--channels' => 'email,phone',
            '--no-interaction' => true,
            ...$overrides,
        ]);
    }

    public function test_an_empty_project_becomes_operational_for_its_domain(): void
    {
        $this->init()->assertSuccessful();

        // Réglages non secrets écrits ; secrets intacts.
        $env = $this->env();
        $this->assertStringContainsString('APP_NAME="Mon Projet"', $env);
        $this->assertStringContainsString("APP_LOCALE=fr\n", $env);
        $this->assertStringContainsString('OFFER_DOMAIN=rental', $env);
        $this->assertStringContainsString('AUTH_CHANNEL_EMAIL=true', $env);
        $this->assertStringContainsString('AUTH_CHANNEL_PHONE=true', $env);
        $this->assertStringContainsString("APP_KEY=base64:secret-key-not-to-touch\nDB_PASSWORD=\"s3cret pass\"\n", $env);

        // Données de départ : catégories du domaine, seul CH actif, rôles.
        $this->assertSame(3, OfferCategory::query()->count());
        $this->assertSame(['CH'], Country::query()->where('active', true)->pluck('code')->all());
        $this->assertTrue(Role::query()->where('name', RoleEnum::MEMBER->value)->exists());

        // Un membre voit « Mes logements » et le formulaire du domaine.
        $member = User::factory()->create(['locale' => 'fr']);
        $member->assignRole(RoleEnum::MEMBER);
        $token = $member->createToken('iPhone')->plainTextToken;
        $menu = $this->withToken($token)->getJson('/api/v1/auth/me/menu')->assertOk()->json('data');
        $this->assertSame('Mes logements', end($menu)['label']);
        $fields = array_column($this->withToken($token)->getJson('/api/v1/forms/offer.offer')->assertOk()->json('data.fields'), 'name');
        $this->assertContains('surface', $fields);
    }

    public function test_replaying_with_the_same_options_changes_nothing(): void
    {
        $this->init()->assertSuccessful();
        $env = $this->env();

        $this->init()->expectsOutputToContain('rien')->assertSuccessful();

        $this->assertSame($env, $this->env());
        $this->assertSame(3, OfferCategory::query()->count());
    }

    public function test_an_invalid_setting_stops_everything_before_writing(): void
    {
        $this->init(['--domain' => 'castle'])->expectsOutputToContain('Domaine inconnu')->assertFailed();
        $this->init(['--locale' => 'xx'])->expectsOutputToContain('Langue inconnue')->assertFailed();
        $this->init(['--country' => ['ZZ']])->expectsOutputToContain('Pays inconnu')->assertFailed();
        $this->init(['--channels' => 'fax'])->assertFailed();
        $this->init(['--name' => "L'\$ami"])->expectsOutputToContain('refusé')->assertFailed();

        $this->assertSame(self::ENV, $this->env());
        $this->assertSame(0, Country::query()->count());
    }

    public function test_production_needs_force(): void
    {
        $this->app['env'] = 'production';

        $this->init()->expectsOutputToContain('--force')->assertFailed();
        $this->assertSame(self::ENV, $this->env());
    }

    public function test_changing_the_domain_of_a_project_with_offers_needs_force(): void
    {
        config(['offer.domain' => 'other']);
        Offer::factory()->create();

        $this->init()->expectsOutputToContain('--force')->assertFailed();
        $this->assertSame(self::ENV, $this->env());

        $this->init(['--force' => true])->assertSuccessful();
    }

    public function test_the_list_shows_known_domains(): void
    {
        $this->artisan('project:init', ['--list' => true])->expectsOutputToContain('rental')->assertSuccessful();
    }

    public function test_the_env_writer_never_lets_a_value_read_another_variable(): void
    {
        $file = new EnvFile($this->directory.'/.env');
        $file->set(['APP_NAME' => '${APP_KEY}x "quoted" #1']);

        $values = Dotenv::createArrayBacked($this->directory)->load();
        $this->assertSame('${APP_KEY}x "quoted" #1', $values['APP_NAME']);
        $this->assertSame('base64:secret-key-not-to-touch', $values['APP_KEY']);

        $file->set(['APP_NAME' => 'Chez "Léa" & co']);
        $this->assertSame('Chez "Léa" & co', Dotenv::createArrayBacked($this->directory)->load()['APP_NAME']);
    }

    public function test_the_env_writer_keeps_line_endings_and_export_lines(): void
    {
        file_put_contents($this->directory.'/.env', "export APP_NAME=Old\r\nDB_PASSWORD=x\r\n");

        (new EnvFile($this->directory.'/.env'))->set(['APP_NAME' => 'New', 'OFFER_DOMAIN' => 'rental']);

        $this->assertSame("APP_NAME=New\r\nDB_PASSWORD=x\r\nOFFER_DOMAIN=rental\r\n", $this->env());
    }

    public function test_the_env_writer_refuses_any_other_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EnvFile::current()->set(['DB_PASSWORD' => 'x']);
    }
}
