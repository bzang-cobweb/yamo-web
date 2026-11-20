<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le type de location remplace « Meublé » et les trois catégories de location
 * (décisions 2026-11-20-contrat-type-de-location et 2026-11-20-type-de-location-garder-categories) :
 * - chaque offre reçoit `details.rental_type` d'après sa catégorie (« Location
 *   longue durée », « Location meublée », « Colocation ») ; sans l'une d'elles,
 *   `furnished` si elle était meublée, sinon `long_term` ;
 * - `details.furnished` disparaît ;
 * - les trois catégories (`long-term`, `furnished`, `shared`) quittent les offres et
 *   sont supprimées ; les autres catégories restent.
 */
return new class extends Migration
{
    private const SLUGS = ['long-term' => 'long_term', 'furnished' => 'furnished', 'shared' => 'shared'];

    public function up(): void
    {
        $categories = DB::table('offer_categories')->whereIn('slug', array_keys(self::SLUGS))->pluck('slug', 'id');

        DB::table('offers')->orderBy('id')->each(function (object $offer) use ($categories): void {
            $details = json_decode((string) ($offer->details ?? '{}'), true);
            $details = is_array($details) ? $details : [];
            $fromCategory = $offer->category_id !== null && isset($categories[$offer->category_id]) ? self::SLUGS[$categories[$offer->category_id]] : null;

            // La catégorie d'abord (ses données passent dans le type de location) ; sans elle, « Meublé ».
            $details['rental_type'] = $fromCategory ?? (($details['furnished'] ?? false) === true ? 'furnished' : ($details['rental_type'] ?? 'long_term'));
            unset($details['furnished']);

            DB::table('offers')->where('id', $offer->id)->update([
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'category_id' => $fromCategory === null ? $offer->category_id : null,
            ]);
        });

        // Les sous-catégories de ces trois catégories remontent à la racine avant leur suppression.
        DB::table('offer_categories')->whereIn('parent_id', $categories->keys())->update(['parent_id' => null]);
        DB::table('offer_categories')->whereIn('id', $categories->keys())->delete();
    }

    public function down(): void
    {
        // Le type de location ne redevient pas « Meublé » : `details.furnished` est rétabli.
        DB::table('offers')->orderBy('id')->each(function (object $offer): void {
            $details = json_decode((string) ($offer->details ?? '{}'), true);
            $details = is_array($details) ? $details : [];
            $details['furnished'] = ($details['rental_type'] ?? null) === 'furnished';
            unset($details['rental_type']);
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });
    }
};
