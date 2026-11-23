<?php

namespace Modules\Partner\Tests\Feature\Filament;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Filament\Resources\PartnerResource;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\EditPartner;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\ListPartners;
use Modules\Partner\Filament\Resources\PartnerResource\RelationManagers\UsersRelationManager;
use Modules\Partner\Models\Partner;

/**
 * Les partenaires au back-office (décision 2026-11-23-module-partenaires).
 */
class PartnerResourceTest extends AuthFilamentTestCase
{
    public function test_partners_are_listed_created_and_edited_with_texts_per_language(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::CREATE_PARTNERS, PermissionEnum::UPDATE_PARTNERS, PermissionEnum::DELETE_PARTNERS);
        $country = Country::factory()->create();
        $existing = Partner::factory()->for($country)->create(['views_count' => 120, 'clicks_count' => 7]);

        Livewire::test(ListPartners::class)->assertCanSeeTableRecords([$existing]);

        Livewire::test(CreatePartner::class)
            ->fillForm([
                'name' => ['fr' => 'Assurances du Littoral', 'en' => 'Littoral Insurance'],
                'country_id' => $country->getKey(),
                'description' => ['fr' => 'Assurance habitation.', 'en' => 'Home insurance.'],
                'url' => ['fr' => 'https://littoral.test/fr', 'en' => 'https://littoral.test/en'],
                'cta_label' => ['fr' => 'Être rappelé', 'en' => 'Call me back'],
                'status' => PartnerStatusEnum::PUBLISHED->value,
                'expires_at' => now()->addMonth()->toDateTimeString(),
                'contact_email' => 'pub@littoral.test',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $partner = Partner::query()->where('name->fr', 'Assurances du Littoral')->sole();
        $this->assertSame('Littoral Insurance', $partner->getTranslation('name', 'en'));
        $this->assertSame('https://littoral.test/en', $partner->getTranslation('url', 'en'));
        $this->assertSame('Être rappelé', $partner->getTranslation('cta_label', 'fr'));
        $this->assertSame(PartnerStatusEnum::PUBLISHED, $partner->status);
        // Rangé à la fin (décision 2026-11-23-partenaires-triables).
        $this->assertSame($existing->position + 1, $partner->position);

        // Le nom, obligatoire dans la langue par défaut.
        Livewire::test(CreatePartner::class)
            ->fillForm(['name' => ['en' => 'Only English'], 'country_id' => $country->getKey()])
            ->call('create')
            ->assertHasFormErrors(['name.fr' => 'required', 'description.fr' => 'required']);

        // Les compteurs se lisent, ils ne s'écrivent pas.
        Livewire::test(EditPartner::class, ['record' => $existing->getKey()])
            ->assertFormSet(['views_count' => 120, 'clicks_count' => 7])
            ->fillForm(['name' => ['fr' => 'Nouveau nom'], 'views_count' => 0])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(['Nouveau nom', 120], [$existing->fresh()->getTranslation('name', 'fr'), $existing->fresh()->views_count]);
        // Recherché dans toutes ses langues.
        Livewire::test(ListPartners::class)->searchTable('Littoral Insurance')->assertCanSeeTableRecords([$partner])->assertCanNotSeeTableRecords([$existing]);
        Livewire::test(ListPartners::class)->searchTable('être rappelé')->assertCanNotSeeTableRecords([$partner]);
        Livewire::test(ListPartners::class)->searchTable('assurances du')->assertCanSeeTableRecords([$partner])->assertCanNotSeeTableRecords([$existing]);
    }

    public function test_partners_are_sorted_by_drag_and_drop(): void
    {
        // Décision 2026-11-23-partenaires-triables.
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        $country = Country::factory()->create();
        [$a, $b, $c] = Partner::factory()->count(3)->for($country)->sequence(['position' => 1], ['position' => 2], ['position' => 3])->create()->all();

        Livewire::test(ListPartners::class)
            ->assertCanSeeTableRecords([$a, $b, $c], inOrder: true)
            ->call('reorderTable', [(string) $c->getKey(), (string) $a->getKey(), (string) $b->getKey()]);

        $this->assertSame([1, 2, 3], [$c->fresh()->position, $a->fresh()->position, $b->fresh()->position]);
    }

    public function test_accounts_are_attached_made_admin_and_detached(): void
    {
        // Décision 2026-11-24-partenaires-par-les-membres.
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        $country = Country::factory()->create();
        $partner = Partner::factory()->for($country)->create();
        $member = User::factory()->create();
        $other = User::factory()->create();
        $partner->users()->attach($other->getKey());

        $this->assertSame('Administrateurs (1)', UsersRelationManager::getTitle($partner, EditPartner::class));
        $manager = fn () => Livewire::test(UsersRelationManager::class, ['ownerRecord' => $partner, 'pageClass' => EditPartner::class]);

        $manager()->assertCanSeeTableRecords([$other])
            ->callTableAction('attach', data: ['recordId' => $member->getKey(), 'is_admin' => true])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($partner->hasAdmin($member->getKey()));

        $manager()->callTableAction('toggleAdmin', $other);
        $this->assertTrue($partner->hasAdmin($other->getKey()));

        $manager()->callTableAction('detach', $member);
        $this->assertFalse($partner->isAdministeredBy($member->getKey()));

        // Le filtre des partenaires proposés depuis l'app.
        $alone = Partner::factory()->for($country)->create();
        Livewire::test(ListPartners::class)->filterTable('proposed', true)->assertCanSeeTableRecords([$partner])->assertCanNotSeeTableRecords([$alone]);
    }

    public function test_the_phone_takes_the_partner_country_dial_code(): void
    {
        // Décision 2026-11-24-partenaire-telephone-pays.
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        $cameroon = Country::factory()->create(['code' => 'CM', 'dial_code' => '+237']);
        $partner = Partner::factory()->for($cameroon)->create(['contact_phone' => '+237670000005']);

        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->assertFormSet(['contact_phone' => '6 70 00 00 05'])
            ->fillForm(['contact_phone' => '6 99 98 06 23'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('+237699980623', $partner->fresh()->contact_phone);

        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['contact_phone' => '12'])
            ->call('save')
            ->assertHasFormErrors(['contact_phone']);
    }

    public function test_a_logo_sent_from_the_back_office_is_optimised_like_from_the_app(): void
    {
        // Décision 2026-11-24-partenaire-logo-optimise : WebP, 512 px au plus, proportions gardées, pas d'original.
        Storage::fake('public');
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        $partner = Partner::factory()->for(Country::factory()->create())->create();

        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['logo' => UploadedFile::fake()->image('logo.png', 2000, 600)])
            ->call('save')
            ->assertHasNoFormErrors();

        $media = $partner->fresh()->getFirstMedia(Partner::LOGO);
        $this->assertNotNull($media);
        $this->assertSame('image/webp', $media->mime_type);
        [$width, $height] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot()));
        // La plus grande conversion (« mine », 960 px) : 2000 × 600 devient 960 × 288 (décisions 2026-11-24-partenaire-logo-taille-unique
        // et 2026-11-24-partenaire-logo-mine-960).
        $this->assertSame([960, 288], [$width, $height]);
        // L'original n'est pas gardé : un seul fichier dans le dossier du logo.
        $this->assertCount(1, Storage::disk('public')->files(dirname($media->getPathRelativeToRoot())));
        $this->assertSame($partner->fresh()->logoUrl(), $media->getUrl());

        // Les réglages viennent de la configuration (`PARTNER_LOGO_*`, décision 2026-11-24-partenaire-logo-config) : la
        // plus grande conversion fixe la taille enregistrée.
        config(['partner.logo.conversions' => ['home' => 200, 'list' => 100, 'mine' => 256, 'detail' => 250]]);
        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['logo' => UploadedFile::fake()->image('logo.png', 2000, 600)])
            ->call('save')
            ->assertHasNoFormErrors();
        $media = $partner->fresh()->getFirstMedia(Partner::LOGO);
        $this->assertSame(256, getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot()))[0]);

        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['logo' => UploadedFile::fake()->create('plaquette.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasFormErrors(['logo']);
    }

    public function test_the_back_office_refuses_a_logo_smaller_than_the_largest_conversion(): void
    {
        // Décision 2026-11-24-logo-taille-minimale.
        Storage::fake('public');
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        $partner = Partner::factory()->for(Country::factory()->create())->create();

        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['logo' => UploadedFile::fake()->image('logo.png', 500, 500)])
            ->call('save')
            ->assertHasFormErrors(['logo']);

        $this->assertNull($partner->fresh()->getFirstMedia(Partner::LOGO));
    }

    public function test_hidden_without_the_permission(): void
    {
        $this->actingAsUserWith();

        $this->get(PartnerResource::getUrl('index'))->assertForbidden();
    }
}
