<?php

namespace Tests\Feature;

use Bza\ConfigViewer\Filament\Pages\ConfigViewerPage;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;

/**
 * Page « Configuration » du back-office (paquet bza/laravel-config-viewer) :
 * configuration effective, modules compris, valeurs sensibles masquées,
 * réservée aux super administrateurs.
 */
class ConfigViewerPageTest extends AuthFilamentTestCase
{
    public function test_a_super_admin_sees_module_configuration(): void
    {
        $this->actingAsSuperAdmin();
        config(['partner.logo.quality' => 77]);

        Livewire::test(ConfigViewerPage::class)
            ->filterTable('group', 'partner')
            ->assertCanSeeTableRecords(['partner.logo.quality'])
            ->assertSee('Module Partner')
            ->assertSee('Modules/Partner/config/config.php');
    }

    public function test_the_configuration_can_be_filtered_by_module(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ConfigViewerPage::class)
            ->filterTable('origin', 'module')
            ->filterTable('module', 'Partner')
            ->assertCanSeeTableRecords(['partner.logo.quality'])
            ->assertCanNotSeeTableRecords(['app.name', 'offer.name']);
    }

    public function test_the_module_filter_only_shows_with_the_module_origin(): void
    {
        $this->actingAsSuperAdmin();

        // The field appears in the same request that selects the origin.
        $page = Livewire::test(ConfigViewerPage::class)->assertDontSeeHtml('tableFilters.module.value');
        $page->set('tableFilters.origin.value', 'module')->assertSeeHtml('tableFilters.module.value');

        // Unselecting the origin hides the field and clears the module.
        $page->set('tableFilters.module.value', 'Partner')
            ->set('tableFilters.origin.value', 'application')
            ->assertDontSeeHtml('tableFilters.module.value')
            ->assertSet('tableFilters.module.value', null)
            ->assertCanSeeTableRecords(['app.name']);
    }

    public function test_the_configuration_can_be_filtered_by_value_type(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ConfigViewerPage::class)
            ->filterTable('group', 'app')
            ->filterTable('type', 'boolean')
            ->assertCanSeeTableRecords(['app.debug'])
            ->assertCanNotSeeTableRecords(['app.name'])
            ->assertSee('Booléen');
    }

    public function test_the_type_filter_shows_all_types_by_default(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(ConfigViewerPage::class)
            ->assertSet('tableFilters.type.value', 'all')
            ->assertSee('Tout')
            ->filterTable('group', 'app')
            ->assertCanSeeTableRecords(['app.debug', 'app.name']);
    }

    public function test_sensitive_values_are_masked(): void
    {
        $this->actingAsSuperAdmin();
        config(['mail.mailers.smtp.password' => 'do-not-leak']);

        Livewire::test(ConfigViewerPage::class)
            ->searchTable('smtp.password')
            ->assertCanSeeTableRecords(['mail.mailers.smtp.password'])
            ->assertSee('••••••••')
            ->assertDontSee('do-not-leak');
    }

    public function test_an_admin_without_the_super_admin_role_is_refused(): void
    {
        $this->actingAsUserWith(PermissionEnum::UPDATE_SETTINGS);

        $this->get(ConfigViewerPage::getUrl())->assertForbidden();
    }

    public function test_the_page_is_in_the_settings_group(): void
    {
        $this->actingAsSuperAdmin();

        $this->assertSame('Paramètres', ConfigViewerPage::getNavigationGroup());
        $this->get(ConfigViewerPage::getUrl())->assertOk();
    }
}
