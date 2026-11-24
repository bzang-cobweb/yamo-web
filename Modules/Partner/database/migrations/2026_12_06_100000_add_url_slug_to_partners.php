<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Partner\Models\Partner;

/**
 * Le slug traduisible d'un partenaire (décision 2026-11-25-slug-url-offres-partenaires) : le lien vers son site,
 * jusqu'ici `url`, devient `website` ; `url` porte le slug, tiré du nom dans chaque langue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->renameColumn('url', 'website');
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->json('url')->nullable()->after('name');
        });

        Partner::query()->orderBy('id')->each(function (Partner $partner): void {
            $partner->generateSlug();
            $partner->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn('url');
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->renameColumn('website', 'url');
        });
    }
};
