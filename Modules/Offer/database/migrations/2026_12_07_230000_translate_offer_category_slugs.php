<?php

use Bza\Form\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Le slug d'une catégorie devient traduit (décision 2026-11-25-categories-slug-traduit) : un par langue, tiré de son
 * nom dans cette langue, unique parmi les catégories du même parent (`HasTranslatableSlug`) ; il n'est plus unique dans
 * toute la table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('offer_categories', ['slug'], 'unique')) {
            Schema::table('offer_categories', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        }
        // Assez de place pour le JSON de toutes les langues, avant de le remplir.
        Schema::table('offer_categories', function (Blueprint $table) {
            $table->text('slug')->change();
        });

        $taken = [];
        DB::table('offer_categories')->orderBy('id')->get()->each(function (object $category) use (&$taken): void {
            $names = (array) json_decode((string) $category->name, true);
            $slugs = [];
            foreach (Locales::values() as $locale) {
                $base = Str::slug((string) ($names[$locale] ?? $names[Locales::default()] ?? '')) ?: (string) $category->slug;
                $slug = $base;
                // Unique parmi ses sœurs, par langue.
                for ($i = 2; isset($taken[$category->parent_id ?? 0][$locale][$slug]); $i++) {
                    $slug = $base.'-'.$i;
                }
                $taken[$category->parent_id ?? 0][$locale][$slug] = true;
                $slugs[$locale] = $slug;
            }
            DB::table('offer_categories')->where('id', $category->id)->update(['slug' => json_encode($slugs, JSON_UNESCAPED_UNICODE)]);
        });

        // Les slugs déjà en JSON : la colonne peut le devenir.
        Schema::table('offer_categories', function (Blueprint $table) {
            $table->json('slug')->change();
        });
    }

    public function down(): void
    {
        Schema::table('offer_categories', function (Blueprint $table) {
            $table->text('slug')->change();
        });
        DB::table('offer_categories')->orderBy('id')->get()->each(function (object $category): void {
            $slugs = (array) json_decode((string) $category->slug, true);
            DB::table('offer_categories')->where('id', $category->id)->update(['slug' => $slugs[Locales::default()] ?? 'categorie-'.$category->id]);
        });
        Schema::table('offer_categories', function (Blueprint $table) {
            $table->string('slug')->change();
        });
        Schema::table('offer_categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
