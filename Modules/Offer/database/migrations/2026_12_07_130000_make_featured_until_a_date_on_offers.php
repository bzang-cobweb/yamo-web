<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En vedette jusqu'à un jour inclus, sans heure (décision 2026-11-25-vedette-en-dates), comme la période payée :
 * `featured_until` devient une date ; l'heure enregistrée est perdue, la date gardée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->date('featured_until')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->timestamp('featured_until')->nullable()->change();
        });
    }
};
