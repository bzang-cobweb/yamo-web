<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Une notification de partenaire par statut (décision 2026-11-24-partenaire-notification-par-statut) : les
 * notifications `PartnerSubmittedNotification` et `PartnerStatusNotification` déjà enregistrées prennent la classe de
 * leur statut (`data.partner_status`, gardé). Une notification d'ajout ou de modification garde `created` inconnu :
 * sa carte reprend le titre enregistré.
 */
return new class extends Migration
{
    private const NAMESPACE = 'Modules\\Partner\\Notifications\\';

    private const SUBMITTED = self::NAMESPACE.'PartnerSubmittedNotification';

    private const STATUS = self::NAMESPACE.'PartnerStatusNotification';

    /** Statut => classe. */
    private const CLASSES = [
        'pending' => 'PartnerPending',
        'published' => 'PartnerPublished',
        'rejected' => 'PartnerRejected',
        'archived' => 'PartnerArchived',
    ];

    public function up(): void
    {
        DB::table('notifications')->whereIn('type', [self::SUBMITTED, self::STATUS])->orderBy('id')->get(['id', 'data'])
            ->each(function (object $row): void {
                $data = json_decode((string) $row->data, true);
                $status = is_array($data) && is_string($data['partner_status'] ?? null) ? $data['partner_status'] : 'pending';

                DB::table('notifications')->where('id', $row->id)->update(['type' => self::NAMESPACE.(self::CLASSES[$status] ?? self::CLASSES['pending'])]);
            });
    }

    public function down(): void
    {
        DB::table('notifications')->where('type', self::NAMESPACE.self::CLASSES['pending'])->update(['type' => self::SUBMITTED]);
        DB::table('notifications')
            ->whereIn('type', array_map(fn (string $class): string => self::NAMESPACE.$class, [self::CLASSES['published'], self::CLASSES['rejected'], self::CLASSES['archived']]))
            ->update(['type' => self::STATUS]);
    }
};
