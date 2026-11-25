<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La devise d'une offre est celle de son pays (décision 2026-11-25-devise-du-pays) : la colonne `offers.currency`, une
 * copie, est retirée. Les réservations gardent la leur (le total d'une demande).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->char('currency', 3)->nullable()->after('price');
        });

        DB::table('offers')->whereNotNull('price')->orderBy('id')->each(function (object $offer): void {
            DB::table('offers')->where('id', $offer->id)->update(['currency' => DB::table('countries')->where('id', $offer->country_id)->value('currency')]);
        });
    }
};
