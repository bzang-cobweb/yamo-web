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
     * @param  list<array{slug: string, name: array<string, string>, children?: list<mixed>}>  $categories
     */
    private function fill(array $categories): void
    {
        foreach ($categories as $category) {
            $row = DB::table('offer_categories')->where('slug', $category['slug'])->first();

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
