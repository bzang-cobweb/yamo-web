<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une offre appartient à un pays (décision 2026-11-18-espace-pays) : celui de son
 * adresse ; sans adresse, le seul pays actif s'il n'y en a qu'un ; sinon aucun
 * (visible dans le back-office seulement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index(['country_id', 'user_id', 'id']);
        });

        $active = DB::table('countries')->where('active', true)->limit(2)->pluck('id');
        $sole = $active->count() === 1 ? $active->first() : null;

        DB::table('offers')->orderBy('id')->each(function (object $offer) use ($sole): void {
            $country = $offer->address_id === null ? null : DB::table('addresses')->where('id', $offer->address_id)->value('country_id');

            DB::table('offers')->where('id', $offer->id)->update(['country_id' => $country ?? $sole]);
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['country_id', 'user_id', 'id']);
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
