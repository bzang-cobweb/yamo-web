<?php

namespace Tests\Feature;

use App\Filament\Resources\SettingResource\Pages\EditSettings;
use App\Models\Setting;
use Filament\Forms\Components\Select;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
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
            ->assertExactJson(['data' => ['name' => config('app.name'), 'description' => null, 'channels' => ['email' => true, 'phone' => true]]]);
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

        $this->withHeader('Accept-Language', 'fr')->get('/')
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

    public function test_home_page_offers_every_language_in_a_dropdown_above_the_logo(): void
    {
        $this->withHeader('Accept-Language', 'fr')->get('/')
            ->assertOk()
            ->assertSee('action="'.route('locale.switch').'"', false)
            ->assertSee('<option value="fr" lang="fr" selected>Français</option>', false)
            ->assertSee('<option value="en" lang="en" >English</option>', false)
            ->assertSeeInOrder(['<select', 'home__logo'], false);
    }

    public function test_home_page_has_no_language_dropdown_with_a_single_language(): void
    {
        config(['app.supported_locales' => ['fr']]);

        $this->get('/')->assertOk()->assertDontSee('<select', false);
    }

    public function test_the_chosen_language_is_kept_for_the_home_page(): void
    {
        Setting::current()->update(['description' => ['fr' => 'Bienvenue', 'en' => 'Welcome']]);

        $this->get('/locale?locale=en')->assertRedirect(route('home'))->assertSessionHas('locale', 'en');

        $this->get('/')->assertSee('Welcome')->assertDontSee('Bienvenue')->assertSee('<html lang="en">', false);
    }

    public function test_the_browser_language_applies_until_one_is_chosen(): void
    {
        Setting::current()->update(['description' => ['fr' => 'Bienvenue', 'en' => 'Welcome']]);

        $this->withHeader('Accept-Language', 'en-GB,en;q=0.9')->get('/')->assertSee('Welcome');
    }

    public function test_an_unknown_language_is_refused(): void
    {
        $this->get('/locale?locale=xx')->assertNotFound();
        $this->get('/locale')->assertNotFound();
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

    public function test_settings_page_applies_payment(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);
        $this->assertFalse(Setting::paymentRequired());

        // Décision 2026-11-19-contrat-paiement-visibilite-avis.
        Livewire::test(EditSettings::class)
            ->assertSee('Appliquer le paiement pour les propriétaires')
            ->assertSee('Appliquer le paiement pour les partenaires')
            ->fillForm(['payment_required' => true, 'partner_payment_required' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Setting::paymentRequired());
        // Décision 2026-11-23-paiement-partenaires.
        $this->assertTrue(Setting::partnerPaymentRequired());
        // Pas servi à l'app.
        $this->getJson('/api/v1/app')->assertJsonMissingPath('data.payment_required')->assertJsonMissingPath('data.partner_payment_required');
    }

    public function test_settings_page_chooses_the_account_notified_of_partner_requests(): void
    {
        // Décision 2026-11-24-partenaire-compte-prevenu : un compte du back-office, pas un membre.
        $team = $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);
        $member = User::factory()->create();
        $this->assertNull(Setting::partnerNotifyUser());

        Livewire::test(EditSettings::class)
            ->assertSee('Compte prévenu des demandes de partenaire')
            ->assertFormFieldExists('partner_notify_user_id', fn (Select $field): bool => array_key_exists($team->getKey(), $field->getOptions())
                && ! array_key_exists($member->getKey(), $field->getOptions()))
            ->fillForm(['partner_notify_user_id' => $team->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($team->is(Setting::partnerNotifyUser()));
        // Pas servi à l'app.
        $this->getJson('/api/v1/app')->assertJsonMissingPath('data.partner_notify_user_id');
    }

    public function test_settings_page_sets_the_home_counts(): void
    {
        // Décision 2026-11-23-accueil-nombres-reglables : 10 sans réglage.
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);
        $this->assertSame([10, 10], [Setting::homeOffersPerCity(), Setting::homePartners()]);

        Livewire::test(EditSettings::class)
            ->assertSee('Offres par ville')
            ->fillForm(['home_offers_per_city' => 6, 'home_partners' => 4])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame([6, 4], [Setting::homeOffersPerCity(), Setting::homePartners()]);

        Livewire::test(EditSettings::class)
            ->fillForm(['home_offers_per_city' => 0, 'home_partners' => 51])
            ->call('save')
            ->assertHasFormErrors(['home_offers_per_city' => 'min', 'home_partners' => 'max']);
    }

    public function test_settings_page_sets_the_featured_block_count(): void
    {
        // Décision 2026-11-25-bloc-a-la-une : 2 sans réglage, de 2 à 4.
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);
        $this->assertSame(2, Setting::homeFeatured());

        Livewire::test(EditSettings::class)
            ->assertSee('Offres « À la une »')
            ->fillForm(['home_featured' => 4])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(4, Setting::homeFeatured());

        Livewire::test(EditSettings::class)
            ->fillForm(['home_featured' => 5])
            ->call('save')
            ->assertHasFormErrors(['home_featured' => 'max']);
        Livewire::test(EditSettings::class)
            ->fillForm(['home_featured' => 1])
            ->call('save')
            ->assertHasFormErrors(['home_featured' => 'min']);
    }
}
