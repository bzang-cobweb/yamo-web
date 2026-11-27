<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La note d'un avis au dixième, de 1,0 à 5,0 (décision 2026-11-27-note-au-dixieme) : 4 devient 4,0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_reviews', function (Blueprint $table): void {
            $table->decimal('rating', 2, 1)->change();
        });
    }

    public function down(): void
    {
        // Retour à l'entier : la note arrondie à l'étoile près.
        DB::table('offer_reviews')->update(['rating' => DB::raw('ROUND(rating)')]);
        Schema::table('offer_reviews', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating')->change();
        });
    }
};
