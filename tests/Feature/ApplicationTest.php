<?php

namespace Tests\Feature;

use App\Filament\Resources\SettingResource\Pages\EditSettings;
use App\Models\Setting;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;

/**
 * Présentation de l'application : réglée dans le back-office (page Application),
 * servie par `GET /api/v1/app` et affichée par la page d'accueil web.
 */
class ApplicationTest extends AuthFilamentTestCase
{
    public function test_api_returns_the_name_and_no_description_by_default(): void
    {
        $this->getJson('/api/v1/app')
            ->assertOk()
            ->assertExactJson(['data' => ['name' => config('app.name'), 'description' => null]]);
    }

    public function test_api_returns_the_description_in_the_requested_language(): void
    {
        Setting::current()->update(['description' => ['fr' => 'Bienvenue', 'en' => 'Welcome']]);

        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/app')->assertJsonPath('data.description', 'Bienvenue');
        $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/app')->assertJsonPath('data.description', 'Welcome');
    }

    public function test_home_page_shows_the_logo_files_and_the_description(): void
    {
        Setting::current()->update(['description' => ['fr' => 'Une app pour se retrouver', 'en' => 'An app to meet']]);

        $this->get('/')
            ->assertOk()
            ->assertSee('images/logo/yamo-logo-vertical-couleur.svg')
            ->assertSee('images/logo/yamo-logo-vertical-inverse.svg')
            ->assertSee('alt="'.e(config('app.name')).'"', false)
            ->assertSee('Une app pour se retrouver');
    }

    public function test_settings_page_has_no_logo_field(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);

        Livewire::test(EditSettings::class)->assertFormFieldDoesNotExist('logo');
    }

    public function test_settings_page_is_forbidden_without_permission(): void
    {
        $this->actingAsUserWith();

        Livewire::test(EditSettings::class)->assertForbidden();
    }

    public function test_settings_page_saves_the_description_per_language(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);

        Livewire::test(EditSettings::class)
            ->assertOk()
            ->assertSee(['Français', 'English'])
            ->fillForm(['description' => ['fr' => 'Bienvenue', 'en' => 'Welcome']])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = Setting::current();
        $this->assertSame(1, Setting::query()->count());
        $this->assertSame('Bienvenue', $setting->getTranslation('description', 'fr'));
        $this->assertSame('Welcome', $setting->getTranslation('description', 'en'));
    }
}
