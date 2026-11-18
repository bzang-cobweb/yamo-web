<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catégories en arbre (décision 2026-11-18-offres-multilingues-categories-arbre) :
 * un parent ou aucun ; `position` ordonne les sœurs. Un parent ne se supprime pas
 * tant qu'il a des enfants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('offer_categories')->restrictOnDelete();
            $table->index(['parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('offer_categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'position']);
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
