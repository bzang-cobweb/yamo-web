<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La période payée en jours, sans heure (décision 2026-11-24-periode-payee-en-jours) : `starts_at` et `expires_at`
 * deviennent des dates ; l'heure enregistrée est perdue, la date gardée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->date('starts_at')->nullable()->change();
            $table->date('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->timestamp('starts_at')->nullable()->change();
            $table->timestamp('expires_at')->nullable()->change();
        });
    }
};
