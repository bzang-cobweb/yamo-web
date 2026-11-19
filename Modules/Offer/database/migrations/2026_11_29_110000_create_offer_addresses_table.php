<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'adresse d'une offre (décision 2026-11-19-contrat-adresse-offre-table) : l'adresse
 * partagée du pays et l'indication propre à l'offre, dans `offer_addresses`. Le lien
 * et l'indication quittent `offers` ; les données existantes sont recopiées.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Lues d'abord : sous SQLite, modifier `offers` reconstruit la table, et la
        // suppression en cascade viderait `offer_addresses` déjà remplie.
        $rows = DB::table('offers')->whereNotNull('address_id')->orderBy('id')->get(['id', 'address_id', 'address_description']);

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('address_description');
        });
        Schema::table('offers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('address_id');
        });

        Schema::create('offer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->constrained()->cascadeOnDelete();
            $table->string('indication', 500)->nullable();
            $table->timestamps();
        });

        foreach ($rows->chunk(500) as $chunk) {
            DB::table('offer_addresses')->insert($chunk->map(fn (object $offer): array => [
                'offer_id' => $offer->id,
                'address_id' => $offer->address_id,
                'indication' => $offer->address_description,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all());
        }
    }

    public function down(): void
    {
        $rows = DB::table('offer_addresses')->orderBy('id')->get(['offer_id', 'address_id', 'indication']);

        Schema::dropIfExists('offer_addresses');

        Schema::table('offers', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->string('address_description', 500)->nullable()->after('address_id');
        });

        foreach ($rows as $row) {
            DB::table('offers')->where('id', $row->offer_id)->update(['address_id' => $row->address_id, 'address_description' => $row->indication]);
        }
    }
};
