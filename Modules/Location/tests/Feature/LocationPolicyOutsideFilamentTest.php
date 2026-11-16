<?php

namespace Modules\Location\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Tests\TestCase;

/**
 * Branche « site et API » des policies : Filament n'est pas en service.
 */
class LocationPolicyOutsideFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_countries_and_regions_are_readable_but_not_writable(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo([PermissionEnum::UPDATE_COUNTRIES, PermissionEnum::UPDATE_REGIONS]);
        $region = Region::factory()->create();

        $this->assertTrue($user->can('viewAny', Country::class));
        $this->assertTrue($user->can('view', $region->country));
        $this->assertTrue($user->can('view', $region));
        $this->assertFalse($user->can('create', Country::class));
        $this->assertFalse($user->can('update', $region->country));
        $this->assertFalse($user->can('update', $region));
        $this->assertFalse($user->can('delete', $region));
    }
}
