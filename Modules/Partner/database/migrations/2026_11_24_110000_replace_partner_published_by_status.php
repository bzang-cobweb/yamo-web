<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le statut de publication d'un partenaire, à la place du booléen `published` (décision 2026-11-24-partenaires-statuts) :
 * publié devient `published`, non publié `pending`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->after('cta_label');
        });
        DB::table('partners')->where('published', true)->update(['status' => 'published']);

        // Le nouvel index d'abord : la clé étrangère du pays a besoin d'un index sur `country_id`.
        Schema::table('partners', function (Blueprint $table): void {
            $table->index(['country_id', 'status', 'position']);
        });
        Schema::table('partners', function (Blueprint $table): void {
            $table->dropIndex(['country_id', 'published', 'position']);
            $table->dropColumn('published');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->boolean('published')->default(false)->after('cta_label');
        });
        DB::table('partners')->where('status', 'published')->update(['published' => true]);

        Schema::table('partners', function (Blueprint $table): void {
            $table->index(['country_id', 'published', 'position']);
        });
        Schema::table('partners', function (Blueprint $table): void {
            $table->dropIndex(['country_id', 'status', 'position']);
            $table->dropColumn('status');
        });
    }
};
