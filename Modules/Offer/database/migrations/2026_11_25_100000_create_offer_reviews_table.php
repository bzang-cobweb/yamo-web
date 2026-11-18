<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avis sur les offres (décision 2026-11-19-contrat-avis-offres) : une note de 1 à
 * 5 et un commentaire, un seul avis par membre et par offre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->timestamps();

            $table->unique(['offer_id', 'user_id']);
            $table->index(['offer_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_reviews');
    }
};
