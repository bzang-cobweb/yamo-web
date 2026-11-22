<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Notifications\BookingNotification;

/**
 * Les notifications de demande déjà enregistrées prennent les textes en vigueur (décision
 * 2026-11-22-reecrire-textes-notifications) : « demande de visite / de séjour », période en chiffres. Seuls le texte
 * (`body`), le texte de la carte (`summary`) et ses passages liés (`text_links`) sont réécrits, dans la langue du
 * destinataire. Le message, le statut à l'envoi et les dates ne bougent pas. Sans demande ou sans destinataire, la
 * notification reste telle quelle. Pas de retour arrière : les anciens textes ne sont pas gardés.
 */
return new class extends Migration
{
    public function up(): void
    {
        $locale = app()->getLocale();
        $types = array_map(fn (BookingStepEnum $step): string => $step->notification(), BookingStepEnum::cases());

        DB::table('notifications')->whereIn('type', $types)->orderBy('id')->get(['id', 'type', 'notifiable_type', 'notifiable_id', 'user_id', 'data'])
            ->each(function (object $row): void {
                $data = json_decode((string) $row->data, true);
                $step = BookingStepEnum::fromNotification((string) $row->type);
                $booking = is_array($data) && is_numeric($data['booking_id'] ?? null) ? Booking::query()->with(['offer', 'user'])->find($data['booking_id']) : null;
                $notifiable = $row->notifiable_type === (new User)->getMorphClass() ? User::query()->find($row->notifiable_id) : null;
                if ($step === null || $booking === null || $booking->offer === null || $booking->user === null || $notifiable === null) {
                    return;
                }

                // Sa propre copie : `own`, ou, pour une ancienne sans ce champ, pas de qui agit.
                $own = (bool) ($data['own'] ?? $row->user_id === null);
                app()->setLocale($notifiable->preferredLocale());
                $fresh = BookingNotification::for($booking, $step, $own, (bool) ($data['edited'] ?? false))->toArray($notifiable);

                DB::table('notifications')->where('id', $row->id)->update(['data' => json_encode([
                    ...$data,
                    'body' => $fresh['body'],
                    'summary' => $fresh['summary'],
                    'text_links' => $fresh['text_links'],
                ])]);
            });

        app()->setLocale($locale);
    }

    public function down(): void
    {
        // Les anciens textes ne sont pas gardés.
    }
};
