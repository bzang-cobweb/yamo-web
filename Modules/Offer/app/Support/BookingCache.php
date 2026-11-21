<?php

namespace Modules\Offer\Support;

use Illuminate\Http\Request;
use Modules\Offer\Models\Booking;

/**
 * Les demandes déjà lues pendant la requête HTTP en cours (décision
 * 2026-11-20-cache-fil) : une demande n'est lue qu'une fois, quel que soit le nombre de
 * notifications de son fil qui la demandent (la pastille, la demande de la page). Une
 * page de notifications les charge toutes d'un coup (`load()`). Une nouvelle requête
 * repart de zéro : le statut d'une demande peut avoir changé.
 */
final class BookingCache
{
    /** @var array<int, Booking|null> */
    private array $bookings = [];

    private ?Request $request = null;

    /**
     * Charge en une requête celles qui ne le sont pas encore.
     *
     * @param  iterable<int>  $ids
     */
    public function load(iterable $ids): void
    {
        $this->fresh();
        $missing = [];
        foreach ($ids as $id) {
            if (! array_key_exists($id, $this->bookings)) {
                $missing[$id] = $id;
            }
        }
        if ($missing === []) {
            return;
        }

        // Avec leur logement : le titre de la carte d'une demande en est tiré.
        $found = Booking::query()->with(['offer.user', 'user'])->whereKey(array_values($missing))->get()->keyBy(fn (Booking $booking): int => (int) $booking->getKey());
        foreach ($missing as $id) {
            $this->bookings[$id] = $found->get($id);
        }
    }

    /** La demande, lue une seule fois ; `null` si elle n'existe plus. */
    public function find(int $id): ?Booking
    {
        $this->load([$id]);

        return $this->bookings[$id];
    }

    /** Une autre requête HTTP (un test en enchaîne plusieurs) : on oublie tout. */
    private function fresh(): void
    {
        $request = app()->bound('request') ? app('request') : null;
        if ($request !== $this->request) {
            $this->request = $request;
            $this->bookings = [];
        }
    }
}
