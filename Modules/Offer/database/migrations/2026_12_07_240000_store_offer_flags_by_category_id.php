<?php

use Bza\Form\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Les règles et caractéristiques d'une offre s'enregistrent par id de catégorie (décision
 * 2026-11-25-categories-slug-traduit) : `"pets": "yes"` devient l'id de « Animaux acceptés » dans `rule_flags`, et ainsi de
 * suite ; un « no » n'est plus rien. Les catégories « Règles » et « Caractéristiques » et leurs cases sont créées si elles
 * manquent.
 */
return new class extends Migration
{
    /** @var array<string, array{root: string, keys: array<string, string>}> */
    private const GROUPS = [
        'rule_flags' => ['root' => 'offer::rental.flag_roots.rules', 'keys' => ['pets' => 'offer::rental.rules.pets', 'smoking' => 'offer::rental.rules.smoking', 'parties' => 'offer::rental.rules.parties']],
        'feature_flags' => ['root' => 'offer::rental.flag_roots.features', 'keys' => ['cameras' => 'offer::rental.cameras', 'borehole' => 'offer::rental.borehole', 'generator' => 'offer::rental.generator']],
    ];

    public function up(): void
    {
        // Les catégories ne sont créées que si une offre en a besoin (une base vide, ou un autre domaine, n'en reçoit pas).
        $ids = null;

        DB::table('offers')->select(['id', 'details'])->orderBy('id')->each(function (object $offer) use (&$ids): void {
            $details = json_decode((string) $offer->details, true);
            if (! is_array($details)) {
                return;
            }
            $changed = false;
            foreach (self::GROUPS as $group => $definition) {
                $checked = [];
                foreach (array_keys($definition['keys']) as $key) {
                    if (! array_key_exists($key, $details)) {
                        continue;
                    }
                    if ($details[$key] === 'yes') {
                        $ids ??= $this->categories();
                        $checked[] = (string) $ids[$group][$key];
                    }
                    unset($details[$key]);
                    $changed = true;
                }
                if ($checked !== []) {
                    $details[$group] = $checked;
                }
            }
            if ($changed) {
                DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    public function down(): void
    {
        $ids = null;

        DB::table('offers')->select(['id', 'details'])->orderBy('id')->each(function (object $offer) use (&$ids): void {
            $details = json_decode((string) $offer->details, true);
            if (! is_array($details) || (! isset($details['rule_flags']) && ! isset($details['feature_flags']))) {
                return;
            }
            $ids ??= $this->categories();
            foreach (array_keys(self::GROUPS) as $group) {
                foreach ((array) ($details[$group] ?? []) as $id) {
                    $key = array_search((int) $id, $ids[$group], true);
                    if ($key !== false) {
                        $details[$key] = 'yes';
                    }
                }
                unset($details[$group]);
            }
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });
    }

    /**
     * Les ids des cases, créées si elles manquent : groupe => clé => id.
     *
     * @return array<string, array<string, int>>
     */
    private function categories(): array
    {
        $ids = [];
        foreach (self::GROUPS as $group => $definition) {
            $root = $this->category($definition['root'], null, 0);
            $position = 0;
            foreach ($definition['keys'] as $key => $label) {
                $ids[$group][$key] = $this->category($label, $root, $position++);
            }
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
