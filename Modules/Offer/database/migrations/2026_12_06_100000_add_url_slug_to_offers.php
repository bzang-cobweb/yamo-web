<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Offer\Models\Offer;

/**
 * Le slug traduisible d'une offre (décision 2026-11-25-slug-url-offres-partenaires) : `url`, tiré du titre dans
 * chaque langue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->json('url')->nullable()->after('title');
        });

        Offer::query()->withTrashed()->orderBy('id')->each(function (Offer $offer): void {
            $offer->generateSlug();
            $offer->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropColumn('url');
        });
    }
};
