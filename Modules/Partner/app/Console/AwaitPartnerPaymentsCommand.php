<?php

namespace Modules\Partner\Console;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;

/**
 * Chaque nuit, le paiement des partenaires appliqué : les partenaires publiés dont la période payée est finie ou
 * absente (`expires_at` passé ou vide) passent « En attente de paiement » (décision 2026-11-24-partenaire-attente-paiement-commande).
 * Ceux en validation ou déjà en attente de paiement ne bougent pas ; un refusé reste refusé. Chaque changement de
 * statut prévient comme au back-office (l'auteur de la dernière demande, ses administrateurs par e-mail et SMS).
 * Le paiement non appliqué : rien.
 */
class AwaitPartnerPaymentsCommand extends Command
{
    protected $signature = 'partner:await-payment';

    protected $description = 'Passe en attente de paiement les partenaires publiés dont la période payée est finie ou absente.';

    public function handle(): int
    {
        if (! Setting::partnerPaymentRequired()) {
            $this->info(__('partner::partner.await_payment.disabled'));

            return self::SUCCESS;
        }

        $count = 0;
        Partner::query()
            ->where('status', PartnerStatusEnum::PUBLISHED)
            ->where(fn (Builder $ended) => $ended->whereNull('expires_at')->orWhereDate('expires_at', '<', today()))
            ->chunkById(100, function ($partners) use (&$count): void {
                foreach ($partners as $partner) {
                    // Un par un : le changement de statut est annoncé (`PartnerStatusChanged`).
                    $partner->update(['status' => PartnerStatusEnum::AWAITING_PAYMENT]);
                    $count++;
                }
            });

        $this->info(trans_choice('partner::partner.await_payment.count', $count, ['count' => $count]));

        return self::SUCCESS;
    }
}
