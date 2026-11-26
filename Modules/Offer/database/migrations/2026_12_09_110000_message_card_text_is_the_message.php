<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Notifications\OfferMessageReceived;

/**
 * La carte d'un message à l'éditeur montre le message lui-même (décision 2026-11-26-carte-message-texte) : les messages
 * déjà enregistrés prennent leur texte (`data.message`) pour `data.summary`, à la place de « Au sujet de « titre » », et
 * perdent le lien du titre de l'offre (`data.text_links`), qui n'est plus dans le texte.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->where('type', OfferMessageReceived::class)->orderBy('id')->select(['id', 'data'])
            ->each(function (object $row): void {
                $data = json_decode((string) $row->data, true);
                if (! is_array($data) || ! is_string($data['message'] ?? null)) {
                    return;
                }

                $data['summary'] = $data['message'];
                $data['text_links'] = [];
                DB::table('notifications')->where('id', $row->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
            });
    }

    /**
     * Rien à remettre : l'ancien texte de la carte (« Au sujet de « titre » ») et son lien n'avaient pas d'autre usage, et
     * la page d'une notification affiche l'offre dans son en-tête.
     */
    public function down(): void {}
};
