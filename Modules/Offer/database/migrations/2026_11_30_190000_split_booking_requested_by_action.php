<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Une notification de demande par action (décision 2026-11-22-notification-par-action) : les notifications
 * `BookingRequested` déjà enregistrées prennent la classe de leur étape (`data.step`, gardée).
 */
return new class extends Migration
{
    private const NAMESPACE = 'Modules\\Offer\\Notifications\\';

    private const MERGED = self::NAMESPACE.'BookingRequested';

    /** Étape => classe. */
    private const CLASSES = [
        'received' => 'BookingRequestReceived',
        'updated' => 'BookingRequestUpdated',
        'accepted' => 'BookingRequestAccepted',
        'refused' => 'BookingRequestRefused',
        'cancelled' => 'BookingRequestCancelled',
    ];

    public function up(): void
    {
        DB::table('notifications')->where('type', self::MERGED)->orderBy('id')->get(['id', 'data'])
            ->each(function (object $row): void {
                $data = json_decode((string) $row->data, true);
                $step = is_array($data) && is_string($data['step'] ?? null) ? $data['step'] : 'received';

                DB::table('notifications')->where('id', $row->id)->update(['type' => self::NAMESPACE.(self::CLASSES[$step] ?? self::CLASSES['received'])]);
            });
    }

    public function down(): void
    {
        DB::table('notifications')
            ->whereIn('type', array_map(fn (string $class): string => self::NAMESPACE.$class, array_values(self::CLASSES)))
            ->update(['type' => self::MERGED]);
    }
};
