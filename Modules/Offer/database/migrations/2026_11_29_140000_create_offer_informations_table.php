<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Informations d'une offre (décision 2026-11-19-contrat-offer-informations) : des
 * blocs titre + description, traduits, dans l'ordre. Le texte « Autres informations »
 * déjà saisi (`details.other_information`) devient un bloc, puis quitte `details`.
 */
return new class extends Migration
{
    /** Le titre donné aux textes repris, par langue. */
    private const TITLES = ['fr' => 'Autres informations', 'en' => 'Other information'];

    public function up(): void
    {
        Schema::create('offer_informations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->json('title');
            $table->json('description');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['offer_id', 'position']);
        });

        DB::table('offers')->whereNotNull('details')->orderBy('id')->each(function (object $offer): void {
            $details = json_decode((string) $offer->details, true) ?: [];
            $text = array_filter((array) ($details['other_information'] ?? []), fn (mixed $value): bool => is_string($value) && trim($value) !== '');

            if (! array_key_exists('other_information', $details)) {
                return;
            }

            if ($text !== []) {
                DB::table('offer_informations')->insert([
                    'offer_id' => $offer->id,
                    'title' => json_encode(array_intersect_key(self::TITLES, $text) ?: ['fr' => self::TITLES['fr']], JSON_UNESCAPED_UNICODE),
                    'description' => json_encode($text, JSON_UNESCAPED_UNICODE),
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            unset($details['other_information']);
            DB::table('offers')->where('id', $offer->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });
    }

    public function down(): void
    {
        // Le premier bloc de chaque offre redevient son texte « Autres informations ».
        DB::table('offer_informations')->orderBy('offer_id')->orderBy('position')->get()->unique('offer_id')->each(function (object $information): void {
            $details = json_decode((string) DB::table('offers')->where('id', $information->offer_id)->value('details'), true) ?: [];
            $details['other_information'] = json_decode((string) $information->description, true);
            DB::table('offers')->where('id', $information->offer_id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
        });

        Schema::dropIfExists('offer_informations');
    }
};
