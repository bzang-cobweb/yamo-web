<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les notifications de demande déjà enregistrées rejoignent le fil de leur demande,
 * `booking:<id>` (décision 2026-11-20-suivi-demande).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->where('type', 'Modules\\Offer\\Notifications\\BookingRequested')->whereNull('thread')->orderBy('id')
            ->get(['id', 'data'])
            ->each(function (object $row): void {
                $data = json_decode((string) $row->data, true);
                $booking = is_array($data) ? ($data['booking_id'] ?? null) : null;
                if (is_int($booking)) {
                    DB::table('notifications')->where('id', $row->id)->update(['thread' => 'booking:'.$booking]);
                }
            });
    }

    public function down(): void
    {
        DB::table('notifications')->where('thread', 'like', 'booking:%')->update(['thread' => null]);
    }
};
