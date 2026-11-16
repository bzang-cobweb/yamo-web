<?php

namespace Modules\Location\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Pays puis régions. Rejouable.
 */
class LocationDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            RegionSeeder::class,
        ]);
    }
}
