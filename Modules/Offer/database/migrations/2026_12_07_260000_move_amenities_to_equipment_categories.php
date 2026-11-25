<?php

use App\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Les anciens équipements (`details.amenities` : wifi, parking…) deviennent des cases de la catégorie « Équipements »
 * (décision 2026-11-25-equipements-en-categories) : leurs ids rejoignent `feature_flags`. Les catégories ne sont créées
 * que si une offre en a besoin.
 */
return new class extends Migration
{
    private const AMENITIES = ['wifi', 'parking', 'air_conditioning', 'hot_water', 'water_tank', 'security', 'equipped_kitchen', 'washing_machine', 'iron', 'tv', 'balcony', 'pool', 'generator'];

    public function up(): void
    {
        $ids = null;

        DB::table('offers')->select(['id', 'details'])->orderBy('id')->each(function (object $offer) use (&$ids): void {
            $details = json_decode((string) $offer->details, true);
            if (! is_array($details) || ! array_key_exists('amenities', $details)) {
                return;
            }
            $codes = array_values(array_intersect((array) $details['amenities'], self::AMENITIES));
            if ($codes !== []) {
                $ids ??= $this->categories();
                $flags = array_map('strval', (array) ($details['feature_flags'] ?? []));
                $details['feature_flags'] = array_values(array_unique([...$flags, ...array_map(fn (string $code): string => (string) $ids[$code], $codes)]));
            }
            unset($details['amenities']);
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });

        if ($ids !== null) {
            $this->order();
        }
    }

    /**
     * Les cases de « Équipements » dans l'ordre de départ : les anciens équipements, puis caméras, forage, groupe
     * électrogène ; les autres ensuite, dans leur ordre.
     */
    private function order(): void
    {
        $root = $this->category('offer::rental.flag_roots.features', null, 0);
        $labels = [...array_map(fn (string $code): string => $code === 'generator' ? 'offer::rental.generator' : 'offer::rental.amenities.'.$code, array_diff(self::AMENITIES, ['generator'])), 'offer::rental.cameras', 'offer::rental.borehole', 'offer::rental.generator'];
        $wanted = array_map(fn (string $label): string => (string) __($label, [], Locales::default()), $labels);
        $children = DB::table('offer_categories')->where('parent_id', $root)->orderBy('position')->orderBy('id')->get();
        $sorted = $children->sortBy(fn (object $category): array => [
            ($index = array_search(json_decode((string) $category->name, true)[Locales::default()] ?? null, $wanted, true)) === false ? PHP_INT_MAX : $index,
            $category->position,
            $category->id,
        ])->values();
        foreach ($sorted as $position => $category) {
            DB::table('offer_categories')->where('id', $category->id)->update(['position' => $position]);
        }
    }

    public function down(): void
    {
        $ids = null;

        DB::table('offers')->select(['id', 'details'])->orderBy('id')->each(function (object $offer) use (&$ids): void {
            $details = json_decode((string) $offer->details, true);
            if (! is_array($details) || ! isset($details['feature_flags'])) {
                return;
            }
            $ids ??= array_flip(array_map('strval', $this->categories()));
            $codes = [];
            $kept = [];
            foreach ((array) $details['feature_flags'] as $id) {
                isset($ids[(string) $id]) && $ids[(string) $id] !== 'generator' ? $codes[] = $ids[(string) $id] : $kept[] = (string) $id;
            }
            $details['amenities'] = $codes;
            $details['feature_flags'] = $kept;
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });
    }

    /**
     * Code => id de sa case sous « Équipements », créées si elles manquent.
     *
     * @return array<string, int>
     */
    private function categories(): array
    {
        $root = $this->category('offer::rental.flag_roots.features', null, 0);
        $ids = [];
        foreach (self::AMENITIES as $position => $code) {
            $ids[$code] = $this->category($code === 'generator' ? 'offer::rental.generator' : 'offer::rental.amenities.'.$code, $root, $position);
        }

        return $ids;
    }

    /** Une catégorie par son nom dans la langue par défaut et son parent, créée si elle manque. */
    private function category(string $label, ?int $parentId, int $position): int
    {
        $names = collect(Locales::values())->mapWithKeys(fn (string $locale): array => [$locale => (string) __($label, [], $locale)])->all();
        $existing = DB::table('offer_categories')->where('parent_id', $parentId)->get()
            ->first(fn (object $category): bool => (json_decode((string) $category->name, true)[Locales::default()] ?? null) === $names[Locales::default()]);
        if ($existing !== null) {
            return (int) $existing->id;
        }

        return (int) DB::table('offer_categories')->insertGetId([
            'parent_id' => $parentId,
            'name' => json_encode($names, JSON_UNESCAPED_UNICODE),
            'slug' => json_encode(array_map(fn (string $name): string => Str::slug($name), $names), JSON_UNESCAPED_UNICODE),
            'position' => $position,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
