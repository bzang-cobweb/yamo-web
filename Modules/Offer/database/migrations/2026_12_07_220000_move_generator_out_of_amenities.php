<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le groupe électrogène n'est plus un équipement mais une caractéristique Oui / Non (décision
 * 2026-11-25-oui-non-trois-positions) : une offre qui l'avait coché a « Oui », et il quitte ses équipements.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('offers')->select(['id', 'details'])->orderBy('id')->each(function (object $offer): void {
            $details = json_decode((string) $offer->details, true);
            $amenities = is_array($details) ? ($details['amenities'] ?? null) : null;
            if (! is_array($amenities) || ! in_array('generator', $amenities, true)) {
                return;
            }

            $details['amenities'] = array_values(array_diff($amenities, ['generator']));
            $details['generator'] = 'yes';
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });
    }

    public function down(): void
    {
        DB::table('offers')->select(['id', 'details'])->orderBy('id')->each(function (object $offer): void {
            $details = json_decode((string) $offer->details, true);
            if (! is_array($details) || ($details['generator'] ?? null) !== 'yes') {
                return;
            }

            $details['amenities'] = array_values(array_unique([...(array) ($details['amenities'] ?? []), 'generator']));
            unset($details['generator']);
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });
    }
};
