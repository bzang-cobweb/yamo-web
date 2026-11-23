<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Appliquer le paiement pour les partenaires (décision 2026-11-23-paiement-partenaires) : coché, un partenaire
 * n'est visible dans l'app que jusqu'à sa date d'expiration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('partner_payment_required')->default(false)->after('payment_required');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('partner_payment_required');
        });
    }
};
