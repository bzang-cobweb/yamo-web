<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les quatre notifications d'une demande deviennent une seule, `BookingRequested`,
 * avec l'étape dans `data.step` (décision 2026-11-20-notification-demande-unique).
 * Les notifications déjà enregistrées suivent : nouveau type, étape ajoutée.
 */
return new class extends Migration
{
    private const NAMESPACE = 'Modules\\Offer\\Notifications\\';

    /** Ancienne classe => étape. */
    private const STEPS = [
        'BookingRequestReceived' => 'received',
        'BookingRequestAccepted' => 'accepted',
        'BookingRequestRefused' => 'refused',
        'BookingRequestCancelled' => 'cancelled',
    ];

    public function up(): void
    {
        foreach (self::STEPS as $class => $step) {
            $this->move(self::NAMESPACE.$class, self::NAMESPACE.'BookingRequested', fn (array $data): array => ['step' => $step, ...$data], $step);
        }
    }

    public function down(): void
    {
        foreach (self::STEPS as $class => $step) {
            $this->move(self::NAMESPACE.'BookingRequested', self::NAMESPACE.$class, function (array $data): array {
                unset($data['step']);

                return $data;
            }, $step, back: true);
        }
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $data
     */
    private function move(string $from, string $to, callable $data, string $step, bool $back = false): void
    {
        DB::table('notifications')->where('type', $from)->orderBy('id')->get(['id', 'data'])
            ->each(function (object $row) use ($to, $data, $step, $back): void {
                $decoded = json_decode((string) $row->data, true);
                $decoded = is_array($decoded) ? $decoded : [];
                // En arrière, seules les notifications de cette étape reprennent cette classe.
                if ($back && ($decoded['step'] ?? 'received') !== $step) {
                    return;
                }

                DB::table('notifications')->where('id', $row->id)->update([
                    'type' => $to,
                    'data' => json_encode($data($decoded), JSON_UNESCAPED_UNICODE),
                ]);
            });
    }
};
