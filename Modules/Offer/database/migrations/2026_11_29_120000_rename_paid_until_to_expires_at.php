<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * « Payée jusqu'au » devient la date d'expiration de l'offre (décision
 * 2026-11-19-contrat-paiement-visibilite-avis) ; les dates sont gardées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('paid_until', 'expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('expires_at', 'paid_until');
        });
    }
};
