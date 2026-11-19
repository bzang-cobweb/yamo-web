<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offres en vedette (jusqu'à une date) et favoris des membres (décision
 * 2026-11-19-contrat-vedette-favoris).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->timestamp('featured_until')->nullable()->index();
        });

        Schema::create('offer_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'offer_id']);
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_favorites');

        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['featured_until']);
            $table->dropColumn('featured_until');
        });
    }
};
