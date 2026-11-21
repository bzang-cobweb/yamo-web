<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le téléphone (E.164) et l'e-mail de contact d'une offre, facultatifs : montrés au membre qui la
 * demande ; vides, ceux du propriétaire (décision 2026-11-21-contact-offre).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('show_address');
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'email']);
        });
    }
};
