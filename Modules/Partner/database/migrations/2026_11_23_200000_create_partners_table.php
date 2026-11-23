<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les partenaires : quelques entreprises qui paient pour un espace sur l'accueil de l'app (décision
 * 2026-11-23-module-partenaires). Le logo est un média (`logo`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            // Affiché seulement dans ce pays, comme les offres (décision 2026-11-18-espace-pays).
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Traduits : un texte par langue.
            $table->json('description')->nullable();
            $table->json('url')->nullable();
            $table->json('cta_label')->nullable();
            $table->boolean('published')->default(false);
            // La campagne : du début (facultatif) à la fin payée (facultative), comme `offers.expires_at`.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            // L'ordre d'affichage, le plus petit d'abord.
            $table->unsignedInteger('position')->default(0);
            // Le contact commercial, au back-office seulement.
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            // Les compteurs, pour justifier le prix auprès du partenaire.
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();

            $table->index(['country_id', 'published', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
