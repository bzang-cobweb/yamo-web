<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Ce qui se paie pour une période (`starts_at`, `expires_at`), une offre ou un partenaire (décision
 * 2026-11-24-visibilite-pays-paiement) : le paiement appliqué (`paymentRequired()`, le réglage de la page Application
 * que le modèle déclare), seulement pendant sa période payée, commencée (début facultatif) et pas terminée ; sans date
 * de fin, jamais. Non appliqué, sans condition. La période se compte en jours, bornes comprises : commencée le jour de
 * son début, payée jusqu'à la fin du jour de sa fin (décision 2026-11-24-periode-payee-en-jours). En scopes pour les requêtes et en méthodes pour un modèle chargé. Le
 * modèle garde les deux colonnes dans ses attributs remplissables ; le trait les lit en dates.
 *
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 */
trait HasPaymentRange
{
    /** Le paiement est-il appliqué à ce modèle (le réglage de la page Application) ? */
    abstract public static function paymentRequired(): bool;

    /** Les deux bornes, des dates sans heure. */
    public function initializeHasPaymentRange(): void
    {
        $this->mergeCasts(['starts_at' => 'date', 'expires_at' => 'date']);
    }

    /**
     * Le paiement appliqué : ceux dans leur période payée, commencée et pas terminée ; sans date de fin, aucun. Non
     * appliqué : tous.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisiblePaymentRange(Builder $query): void
    {
        $query->when(static::paymentRequired(), fn (Builder $paid) => $paid
            ->paymentStarted()
            ->whereDate($query->qualifyColumn('expires_at'), '>=', today()));
    }

    /**
     * Ceux dont la période a commencé (sans début : commencée), que le paiement soit appliqué ou non.
     *
     * @param  Builder<static>  $query
     */
    public function scopePaymentStarted(Builder $query): void
    {
        $query->where(fn (Builder $start) => $start->whereNull($query->qualifyColumn('starts_at'))->orWhereDate($query->qualifyColumn('starts_at'), '<=', today()));
    }

    /**
     * Dans sa période payée, ou le paiement n'est pas appliqué : la règle de `scopeVisiblePaymentRange()`, pour un modèle
     * chargé. `$required` : le réglage déjà lu (une liste le lit une fois pour toutes ses lignes) ; à défaut, il est lu.
     */
    public function isInPaymentRange(?bool $required = null): bool
    {
        if (! ($required ?? static::paymentRequired())) {
            return true;
        }
        if (! $this->isPaymentStarted()) {
            return false;
        }

        return $this->isPaymentRunning();
    }

    /** La période a-t-elle commencé ? La règle de `scopePaymentStarted()`, pour un modèle chargé. */
    public function isPaymentStarted(): bool
    {
        $start = $this->getAttribute('starts_at');

        return $start === null || $start->lte(today());
    }

    /** La période payée court-elle encore aujourd'hui ? Sa fin est aujourd'hui ou plus tard ; sans fin, non. */
    public function isPaymentRunning(): bool
    {
        $end = $this->getAttribute('expires_at');

        return $end !== null && $end->gte(today());
    }
}
