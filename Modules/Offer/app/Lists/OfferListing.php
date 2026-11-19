<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Forms\OfferForm;
use Modules\Offer\Lists\Concerns\FiltersOffers;
use Modules\Offer\Models\Offer;

/**
 * Les offres du compte (« Mes logements »), ouvertes depuis le menu du profil :
 * titre, statut et prix ; recherche dans le titre ; création, édition et retrait
 * (décision 2026-11-18-offres-et-domaines). Sans domaine choisi, la liste
 * n'existe pas.
 */
class OfferListing extends Listing
{
    use FiltersOffers;

    public static function name(): string
    {
        return 'offer.mine';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function title(): string
    {
        return app(DomainRegistry::class)->require()->mineLabel();
    }

    public function emptyText(): string
    {
        return __('offer::offer.list.empty');
    }

    /**
     * « Ajouter un logement » dans la barre fixe en bas, plus sous la recherche
     * (décision 2026-11-19-contrat-reservations-logements-profil). Pas proposé à qui
     * ne peut pas créer, ni au-delà d'une offre quand le domaine l'impose.
     */
    public function footer(): ?array
    {
        $domain = app(DomainRegistry::class)->require();

        if ($this->user === null || ! $this->user->can('create', Offer::class)
            || ($domain->onePerAccount() && Offer::query()->where('user_id', $this->user->getAuthIdentifier())->where('country_id', CurrentCountry::require()->getKey())->exists())) {
            return null;
        }

        return ['label' => $domain->addLabel(), 'form' => OfferForm::name(), 'link' => null];
    }

    /**
     * Le statut de l'annonce, puis les filtres de l'accueil, parmi ses propres offres
     * (décision 2026-11-19-contrat-reservations-logements-profil).
     */
    public function filters(): array
    {
        return [
            Field::select('status')
                ->label(__('offer::booking.profile.offer_status'))
                ->options(OfferStatusEnum::options()),
            // Les logements qui ont au moins une demande dans ce statut : à venir, en attente, passée.
            BookingFilters::status('booking_status', __('offer::booking.profile.filter_booking_status')),
            ...$this->offerFilters($this->mine()),
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $group = BookingFilters::group($filters, 'booking_status');
        if ($group !== null) {
            $query->whereHas('bookings', fn (Builder $bookings) => $bookings->inStatusGroup($group));
        }
        $this->applyOfferFilters($query, $filters);
    }

    /**
     * Ses offres dans le pays courant.
     *
     * @return Builder<Offer>
     */
    private function mine(): Builder
    {
        return Offer::query()
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey());
    }

    public function searchPlaceholder(): string
    {
        return __('offer::offer.list.search');
    }

    /**
     * @return Builder<Offer>
     */
    public function query(): Builder
    {
        // Les siennes, dans le pays courant (décision 2026-11-18-espace-pays).
        return $this->mine()
            ->with(['media', 'address'])
            // Moyenne, nombre et dernier avis (décision 2026-11-19-contrat-avis-offres).
            ->withReviewSummary()
            // Les demandes en attente, pour le compteur de la carte.
            ->withCount(['bookings as pending_bookings_count' => fn (Builder $bookings) => $bookings->where('status', BookingStatusEnum::PENDING)])
            ->orderBy('id');
    }

    protected function search(Builder $query, string $term): void
    {
        // Le titre, toutes langues confondues.
        $this->orWhereContains($query, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
    }

    public function item(Model $record): ListItem
    {
        /** @var Offer $record */
        // « 60 000 FCFA / jour » : le prix et son unité, selon le domaine.
        $price = app(DomainRegistry::class)->require()->formattedPrice($record, app()->getLocale());

        $locale = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();

        // Le titre construit par le domaine (« Studio · meublé · Douala »), sinon le titre saisi.
        $domain = app(DomainRegistry::class)->require();
        $typed = (string) $record->getTranslation('title', $locale);
        $title = $domain->itemTitle($record) ?? $typed;

        // La date d'expiration, après le statut (décision 2026-11-19-expiration-mes-logements) :
        // « Expire le 30 juin 2027 », « Expirée le 2 janvier 2026 » ; sans date, rien.
        $expiration = $record->expires_at === null ? null : __(
            $record->expires_at->isFuture() ? 'offer::offer.list.expires' : 'offer::offer.list.expired',
            ['date' => $record->expires_at->copy()->locale($locale)->isoFormat('LL')],
            $locale,
        );

        // En sous-titre, le titre saisi ; en gras, « Publiée · 80 000 FCFA / mois »
        // (décision 2026-11-19-item-titre-saisi-details). La note, tant qu'elle est publiée.
        $item = ListItem::make($record->getKey(), $title, $typed === $title ? null : $typed)
            ->image(MediaUrl::onRequestHost($record->photoUrl()))
            ->rating($record->showsReviews() ? $record->reviews_avg_rating : null, (int) $record->reviews_count)
            // Le statut en pastille de couleur, comme dans « Mes réservations » (décision 2026-11-19-statut-carte-logement).
            ->tag($record->status->label(), $record->status->tone())
            ->highlight(implode(' · ', array_filter([$expiration, ...$domain->itemDetails($record), $price])))
            ->edit(OfferForm::name())
            // « Voir » : sa fiche, brouillon compris (décision 2026-11-19-nuit-voir-editer-retirer).
            ->link('/offers/'.$record->getKey())
            // Son calendrier : occupation, demandes, jours bloqués (décision 2026-11-19-contrat-calendrier-offre).
            ->action(__('offer::booking.calendar.button'), link: '/offers/'.$record->getKey().'/calendar')
            ->delete(
                __('offer::offer.list.remove'),
                __('offer::offer.list.confirm'),
                Submission::toRoute(
                    'api.v1.offers.destroy',
                    SuccessAction::BACK,
                    __('offer::offer.list.removed'),
                    destructive: true,
                    parameters: ['offer' => $record->getKey()],
                ),
            );

        // Ses demandes en attente : un compteur qui ouvre les demandes de ce logement
        // (décision 2026-11-19-contrat-reservations-logements-profil).
        $pending = (int) ($record->pending_bookings_count ?? 0);

        return $pending === 0 ? $item : $item->counter(
            trans_choice('offer::booking.profile.pending_count', $pending, ['count' => $pending]),
            ReceivedBookingListing::name(),
            ['offer' => (string) $record->getKey(), 'status' => 'pending'],
        );
    }
}
