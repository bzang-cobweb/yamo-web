<?php

use Bza\Form\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le nom d'un partenaire, traduit lui aussi (décision 2026-11-23-partenaires-nom-traduit) : le nom actuel devient
 * celui de la langue par défaut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->json('name_translations')->nullable()->after('name');
        });

        foreach (DB::table('partners')->get(['id', 'name']) as $partner) {
            DB::table('partners')->where('id', $partner->id)->update(['name_translations' => json_encode([Locales::default() => $partner->name])]);
        }

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn('name');
        });
        Schema::table('partners', function (Blueprint $table): void {
            $table->renameColumn('name_translations', 'name');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->string('name_plain')->default('')->after('name');
        });

        foreach (DB::table('partners')->get(['id', 'name']) as $partner) {
            $names = json_decode((string) $partner->name, true) ?: [];
            DB::table('partners')->where('id', $partner->id)->update(['name_plain' => (string) ($names[Locales::default()] ?? reset($names) ?: '')]);
        }

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn('name');
        });
        Schema::table('partners', function (Blueprint $table): void {
            $table->renameColumn('name_plain', 'name');
        });
    }
};
