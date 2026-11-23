<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Le téléphone d'un partenaire au format international, avec l'indicatif de son pays (décision
 * 2026-11-24-partenaire-telephone-pays) ; un numéro illisible est gardé tel quel.
 */
return new class extends Migration
{
    public function up(): void
    {
        $partners = DB::table('partners')->join('countries', 'countries.id', '=', 'partners.country_id')
            ->whereNotNull('partners.contact_phone')
            ->get(['partners.id', 'partners.contact_phone', 'countries.code']);

        foreach ($partners as $partner) {
            try {
                $phone = PhoneNumbers::toE164((string) $partner->contact_phone, (string) $partner->code);
            } catch (Throwable) {
                continue;
            }
            DB::table('partners')->where('id', $partner->id)->update(['contact_phone' => $phone]);
        }
    }

    public function down(): void {}
};
