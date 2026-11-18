<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Location\Support\Currencies;

/**
 * Devise de chaque pays (décision 2026-11-18-espace-pays), tirée des données ICU
 * (Cameroun → XAF) ; modifiable ensuite dans le back-office.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->char('currency', 3)->nullable()->after('dial_code');
        });

        DB::table('countries')->orderBy('id')->each(function (object $country): void {
            DB::table('countries')->where('id', $country->id)->update(['currency' => Currencies::forCountry($country->code)]);
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
