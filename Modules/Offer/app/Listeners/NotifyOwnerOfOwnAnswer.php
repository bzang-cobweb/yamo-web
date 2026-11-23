<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Services\BookingNotifier;

/**
 * Une réponse : le propriétaire la retrouve dans son propre fil, déjà lue, et sa carte
 * remonte en tête (décision 2026-11-20-reponse-dans-son-fil).
 */
final class NotifyOwnerOfOwnAnswer
{
    public function __construct(
        private readonly BookingNotifier $notifier,
    ) {}

    public function handle(BookingAnswered $event): void
    {
        $this->notifier->ownerOfOwnAnswer($event->booking);
    }
}
