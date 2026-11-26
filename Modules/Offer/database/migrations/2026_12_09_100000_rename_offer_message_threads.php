<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le fil d'une discussion avec l'éditeur prend pour préfixe la clé du type « Message » du filtre des notifications
 * (`OfferNotificationFilter::MESSAGE`, décision 2026-11-26-prefixes-de-fil-des-filtres) : `offer-message:12:37`
 * devient `offer_message:12:37`. Les fils des demandes (`booking:`) et des partenaires (`partner:group:`) ne changent pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('offer-message:', 'offer_message:');
    }

    public function down(): void
    {
        $this->rename('offer_message:', 'offer-message:');
    }

    private function rename(string $from, string $to): void
    {
        // Ligne par ligne : la même requête sous MariaDB et SQLite.
        DB::table('notifications')->where('thread', 'like', $from.'%')->orderBy('id')->select(['id', 'thread'])
            ->each(fn (object $row) => DB::table('notifications')->where('id', $row->id)
                ->update(['thread' => $to.substr((string) $row->thread, strlen($from))]));
    }
};
