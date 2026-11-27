<?php

use Bza\Form\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * La catégorie racine « Caractéristiques » s'appelle « Équipements » (décision 2026-11-25-categories-slug-traduit) : son
 * nom et son slug, dans chaque langue. Le code la retrouve par son slug dans la langue par défaut.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename(['fr' => 'Caractéristiques', 'en' => 'Features', 'de' => 'Merkmale'], ['fr' => 'Équipements', 'en' => 'Amenities', 'de' => 'Ausstattung']);
    }

    public function down(): void
    {
        $this->rename(['fr' => 'Équipements', 'en' => 'Amenities', 'de' => 'Ausstattung'], ['fr' => 'Caractéristiques', 'en' => 'Features', 'de' => 'Merkmale']);
    }

    /**
     * @param  array<string, string>  $from
     * @param  array<string, string>  $to
     */
    private function rename(array $from, array $to): void
    {
        $root = DB::table('offer_categories')->whereNull('parent_id')->get()
            ->first(fn (object $category): bool => (json_decode((string) $category->name, true)[Locales::default()] ?? null) === ($from[Locales::default()] ?? null));
        if ($root === null) {
            return;
        }

        $names = array_merge((array) json_decode((string) $root->name, true), array_intersect_key($to, array_flip(Locales::values())));
        $slugs = array_map(fn (string $name): string => Str::slug($name), $names);
        DB::table('offer_categories')->where('id', $root->id)->update([
            'name' => json_encode($names, JSON_UNESCAPED_UNICODE),
            'slug' => json_encode($slugs, JSON_UNESCAPED_UNICODE),
        ]);
    }
};
