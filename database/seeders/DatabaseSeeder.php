<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Location\Database\Seeders\LocationDatabaseSeeder;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AuthDatabaseSeeder::class,
            LocationDatabaseSeeder::class,
            OfferDatabaseSeeder::class,
        ]);
    }
}
