<?php

use App\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Une case « Parking gratuit » dans « Équipements », juste après « Parking » (décision 2026-11-26-parking-gratuit), là
 * où la catégorie existe déjà ; une nouvelle base la reçoit du seeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        $root = $this->find('offer::rental.flag_roots.features', null);
        if ($root === null || $this->find('offer::rental.free_parking', $root->id) !== null) {
            return;
        }

        // Juste après « Parking » ; sans lui, à la fin.
        $parking = $this->find('offer::rental.amenities.parking', $root->id);
        $position = $parking === null
            ? (int) DB::table('offer_categories')->where('parent_id', $root->id)->max('position') + 1
            : (int) $parking->position + 1;
        DB::table('offer_categories')->where('parent_id', $root->id)->where('position', '>=', $position)->increment('position');

        $names = collect(Locales::values())->mapWithKeys(fn (string $locale): array => [$locale => (string) __('offer::rental.free_parking', [], $locale)])->all();
        DB::table('offer_categories')->insert([
            'parent_id' => $root->id,
            'name' => json_encode($names, JSON_UNESCAPED_UNICODE),
            'slug' => json_encode(array_map(fn (string $name): string => Str::slug($name), $names), JSON_UNESCAPED_UNICODE),
            'position' => $position,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $root = $this->find('offer::rental.flag_roots.features', null);
        $freeParking = $root === null ? null : $this->find('offer::rental.free_parking', $root->id);
        if ($freeParking === null) {
            return;
        }

        DB::table('offer_categories')->where('id', $freeParking->id)->delete();
        DB::table('offer_categories')->where('parent_id', $root->id)->where('position', '>', $freeParking->position)->decrement('position');
    }

    /** Une catégorie par son nom dans la langue par défaut et son parent, ou `null`. */
    private function find(string $label, ?int $parentId): ?object
    {
        $name = (string) __($label, [], Locales::default());

        return DB::table('offer_categories')->where('parent_id', $parentId)->get()
            ->first(fn (object $row): bool => (json_decode((string) $row->name, true)[Locales::default()] ?? null) === $name);
    }
};
