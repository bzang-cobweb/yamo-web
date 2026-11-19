<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les jours qu'un propriétaire bloque sur son offre (décision
 * 2026-11-19-contrat-calendrier-offre) : ni demandables, ni acceptables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();

            $table->unique(['offer_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_blocked_dates');
    }
};
