<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les listes de favoris nommées et leurs offres (décision 2026-11-27-listes-de-favoris). `kind` : `custom` (une liste
 * nommée par le membre) ou `recent` (« Récemment consultés », une par membre, sans nom saisi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60)->nullable();
            $table->string('kind', 16)->default('custom');
            $table->timestamps();

            $table->index(['user_id', 'kind']);
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['wishlist_id', 'offer_id']);
            $table->index(['wishlist_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
    }
};
