<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Position d'une région dans son pays (décision 2026-11-17-tri-regions-menu-profil).
 * Les régions existantes prennent l'ordre alphabétique français, celui qu'affichait
 * jusqu'ici le champ Région.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('active');
            $table->index(['country_id', 'position']);
        });

        $collator = new Collator('fr');

        DB::table('regions')->get(['id', 'country_id', 'name'])
            ->groupBy('country_id')
            ->each(function ($regions) use ($collator): void {
                $regions
                    ->sort(fn (object $a, object $b): int => (int) $collator->compare(
                        (string) (json_decode($a->name, true)['fr'] ?? ''),
                        (string) (json_decode($b->name, true)['fr'] ?? ''),
                    ))
                    ->values()
                    ->each(fn (object $region, int $index) => DB::table('regions')
                        ->where('id', $region->id)
                        ->update(['position' => $index + 1]));
            });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex(['country_id', 'position']);
            $table->dropColumn('position');
        });
    }
};
