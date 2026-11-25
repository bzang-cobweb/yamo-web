<?php

use App\Support\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * « Forage » faisait double emploi avec « Réserve d'eau / forage » (décision 2026-11-25-forage-en-double) : sa case est
 * retirée, et une offre qui l'avait cochée a « Réserve d'eau / forage » à la place.
 */
return new class extends Migration
{
    public function up(): void
    {
        $root = $this->id('offer::rental.flag_roots.features', null);
        $borehole = $root === null ? null : $this->id('offer::rental.borehole', $root);
        if ($borehole === null) {
            return;
        }
        $waterTank = $this->id('offer::rental.amenities.water_tank', $root);

        DB::table('offers')->select(['id', 'details'])->orderBy('id')->each(function (object $offer) use ($borehole, $waterTank): void {
            $details = json_decode((string) $offer->details, true);
            $flags = is_array($details) ? array_map('strval', (array) ($details['feature_flags'] ?? [])) : [];
            if (! in_array((string) $borehole, $flags, true)) {
                return;
            }
            $flags = array_values(array_diff($flags, [(string) $borehole]));
            if ($waterTank !== null && ! in_array((string) $waterTank, $flags, true)) {
                $flags[] = (string) $waterTank;
            }
            $details['feature_flags'] = $flags;
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });

        DB::table('offer_categories')->where('id', $borehole)->delete();
    }

    public function down(): void
    {
        // La case retirée n'est pas recréée : le seeder ne la déclare plus.
    }

    /** L'id d'une catégorie par son nom dans la langue par défaut et son parent, ou `null`. */
    private function id(string $label, ?int $parentId): ?int
    {
        $name = (string) __($label, [], Locales::default());
        $category = DB::table('offer_categories')->where('parent_id', $parentId)->get()
            ->first(fn (object $row): bool => (json_decode((string) $row->name, true)[Locales::default()] ?? null) === $name);

        return $category === null ? null : (int) $category->id;
    }
};
