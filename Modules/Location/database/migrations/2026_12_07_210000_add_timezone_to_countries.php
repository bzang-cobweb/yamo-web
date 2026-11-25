<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Location\Support\Timezones;

/**
 * Le fuseau horaire de chaque pays (décision 2026-11-25-fuseau-par-pays), tiré de la base de fuseaux de PHP
 * (Cameroun → Africa/Douala) ; modifiable ensuite dans le back-office. Sert à dire « aujourd'hui » chez l'offre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('currency');
        });

        DB::table('countries')->orderBy('id')->each(function (object $country): void {
            DB::table('countries')->where('id', $country->id)->update(['timezone' => Timezones::forCountry($country->code)]);
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
