<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'indication d'une adresse d'offre devient traduisible (décision
 * 2026-11-19-indication-traduisible) : un texte existant est rangé dans la langue
 * d'origine de l'offre (le français à défaut). Les valeurs sont lues avant de
 * modifier la table, puis réécrites.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('offer_addresses')
            ->leftJoin('offers', 'offers.id', '=', 'offer_addresses.offer_id')
            ->whereNotNull('offer_addresses.indication')
            ->get(['offer_addresses.id', 'offer_addresses.indication', 'offers.locale']);

        Schema::table('offer_addresses', function (Blueprint $table) {
            $table->dropColumn('indication');
        });
        Schema::table('offer_addresses', function (Blueprint $table) {
            $table->json('indication')->nullable()->after('address_id');
        });

        foreach ($rows as $row) {
            if (trim((string) $row->indication) !== '') {
                DB::table('offer_addresses')->where('id', $row->id)->update(['indication' => json_encode([($row->locale ?: 'fr') => $row->indication], JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        $rows = DB::table('offer_addresses')->whereNotNull('indication')->get(['id', 'indication']);

        Schema::table('offer_addresses', function (Blueprint $table) {
            $table->dropColumn('indication');
        });
        Schema::table('offer_addresses', function (Blueprint $table) {
            $table->string('indication', 500)->nullable()->after('address_id');
        });

        foreach ($rows as $row) {
            $texts = array_values(array_filter((array) json_decode((string) $row->indication, true)));
            DB::table('offer_addresses')->where('id', $row->id)->update(['indication' => $texts[0] ?? null]);
        }
    }
};
