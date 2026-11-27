<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Domains\DomainRegistry;

/**
 * Les noms des catégories de départ du domaine dans une langue ajoutée à l'app
 * (l'allemand, décision 2026-11-19-contrat-langue-saisie-allemand) : seules les
 * langues manquantes sont ajoutées, un nom déjà présent n'est pas touché.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->fill(app(DomainRegistry::class)->active()?->categories() ?? []);
    }

    public function down(): void {}

    /**
     * @param  list<array{slug?: string, name: array<string, string>, children?: list<mixed>}>  $categories
     */
    private function fill(array $categories): void
    {
        foreach ($categories as $category) {
            // Les catégories d'un domaine n'ont plus de slug (tiré des noms par le seeder) : seules celles qui en
            // donnent un sont retrouvées ; les autres sont sautées, leurs enfants restent parcourus.
            $slug = $category['slug'] ?? null;
            $row = $slug !== null ? DB::table('offer_categories')->where('slug', $slug)->first() : null;

            if ($row !== null) {
                $names = (array) json_decode((string) $row->name, true);
                $merged = [...$category['name'], ...array_filter($names)];

                if ($merged !== $names) {
                    DB::table('offer_categories')->where('id', $row->id)->update(['name' => json_encode($merged, JSON_UNESCAPED_UNICODE)]);
                }
            }

            $this->fill($category['children'] ?? []);
        }
    }
};
