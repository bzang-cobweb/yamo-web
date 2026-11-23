<?php

namespace Modules\Partner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerLogoService;
use Tests\TestCase;

/**
 * Une conversion du logo par endroit où il s'affiche, réglée dans la configuration (décision
 * 2026-11-24-partenaire-logo-conversions).
 */
class PartnerLogoConversionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_screen_serves_its_own_conversion_sized_by_the_configuration(): void
    {
        Storage::fake('public');
        config(['partner.logo.conversions.list' => 100]);
        $cameroon = Country::factory()->create(['code' => 'CM']);
        $partner = Partner::factory()->for($cameroon)->create();
        $member = User::factory()->create(['locale' => 'fr']);
        $member->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));
        $partner->users()->attach($member->getKey(), ['is_admin' => true]);

        $file = UploadedFile::fake()->image('logo.png', 2000, 1000);
        $media = app(PartnerLogoService::class)->store($partner, $file->getRealPath());

        // Produites tout de suite, à leur taille (le grand côté), en WebP.
        $sizes = [];
        foreach (['home', 'list', 'mine', 'detail'] as $name) {
            $this->assertTrue($media->fresh()->hasGeneratedConversion($name), $name);
            $sizes[$name] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot($name)))[0];
        }
        $this->assertSame(['home' => 288, 'list' => 100, 'mine' => 480, 'detail' => 360], $sizes);

        // Chaque écran sert la sienne.
        $headers = ['Accept-Language' => 'fr', 'X-Country' => 'CM'];
        $this->assertStringContainsString('-home.webp', $this->withHeaders($headers)->getJson('/api/v1/partners')->json('data.0.logo'));
        $this->assertStringContainsString('-detail.webp', $this->withHeaders($headers)->getJson("/api/v1/partners/{$partner->getKey()}")->json('data.logo'));
        $this->assertStringContainsString('-list.webp', $this->withHeaders($headers)->getJson('/api/v1/lists/partner.all')->json('data.items.0.image'));
        $this->assertStringContainsString('-mine.webp', $this->withHeaders($headers)->withToken($member->createToken('iPhone')->plainTextToken)->getJson('/api/v1/lists/partner.mine')->json('data.items.0.image'));
    }

    public function test_without_its_conversion_the_logo_itself_is_served(): void
    {
        Storage::fake('public');
        $partner = Partner::factory()->for(Country::factory()->create(['code' => 'CM']))->create();
        $file = UploadedFile::fake()->image('logo.png', 300, 300);
        $media = app(PartnerLogoService::class)->store($partner, $file->getRealPath());
        // Un logo enregistré avant les conversions : la sienne n'est pas produite.
        $media->markAsConversionNotGenerated('home');
        $media->save();

        $this->assertSame($media->getUrl(), $partner->fresh()->logoUrl('home'));
        $this->assertStringContainsString('-detail.webp', (string) $partner->fresh()->logoUrl('detail'));
    }
}
