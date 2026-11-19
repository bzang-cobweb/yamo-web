<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * « Appliquer le paiement » (décision 2026-11-19-contrat-paiement-visibilite-avis) :
 * coché, une offre n'est visible des autres que jusqu'à sa date d'expiration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('payment_required')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('payment_required');
        });
    }
};
