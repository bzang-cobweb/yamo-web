<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
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
use Modules\Offer\Forms\BookingRefuseForm;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * « Réservations reçues » (décision 2026-11-19-contrat-reservations) : les demandes
 * sur les offres du compte dans le pays courant, la plus récente en tête ; le
 * membre, l'offre, les dates, le statut ; « Répondre » tant qu'elle attend.
 */
class ReceivedBookingListing extends Listing
{
    public static function name(): string
    {
        return 'offer.bookings.received';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function emptyText(): string
    {
        return __('offer::booking.list.received_empty');
    }

    public function searchPlaceholder(): string
    {
        return __('offer::booking.list.search');
    }

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()
            ->with(['offer.media', 'offer.address', 'user'])
            ->whereHas('offer', fn (Builder $offer) => $offer->withTrashed()
                ->where('user_id', $this->user?->getAuthIdentifier())
                ->where('country_id', CurrentCountry::require()->getKey()))
            ->orderByDesc('id');
    }

    protected function search(Builder $query, string $term): void
    {
        $query->orWhereHas('offer', fn (Builder $offer) => $offer->withTrashed()->where(
            fn (Builder $columns) => $this->orWhereContains($columns, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term),
        ));
    }

    public function item(Model $record): ListItem
    {
        /** @var Booking $record */
        $locale = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
        // Le message du membre, cité sur la carte (décision 2026-11-19-message-sur-les-demandes-recues).
        $item = BookingItem::make($record, $locale, withGuest: true)
            ->quote($record->message)
            ->link('/offers/'.$record->offer_id);

        if (! $record->isAwaitingAnswer()) {
            return $item;
        }

        // Sur la carte : « Accepter » après confirmation, « Refuser » avec un message
        // facultatif (décision 2026-11-19-contrat-reservations-logements-profil).
        return $item
            ->action(
                __('offer::booking.profile.accept'),
                'primary',
                __('offer::booking.profile.accept_confirm', ['guest' => (string) $record->user->full_name, 'period' => $record->period($locale)], $locale),
                Submission::toRoute('api.v1.bookings.accept', SuccessAction::BACK, __('offer::booking.profile.accepted_notice'), parameters: ['booking' => $record->getKey()]),
            )
            ->action(__('offer::booking.profile.refuse'), 'destructive', form: BookingRefuseForm::name());
    }

    /**
     * Le logement concerné, puis le statut (« En attente » ouvert depuis le compteur d'un logement).
     */
    public function filters(): array
    {
        return [
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->offers()),
            BookingFilters::status(),
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['offer'])) {
            $query->where('offer_id', $filters['offer']);
        }
        BookingFilters::applyStatus($query, $filters);
    }

    /**
     * Filtrée sur un logement : « Demandes · Studio meublé » ; sinon « Demandes reçues ».
     */
    public function titleFor(array $filters): string
    {
        $offers = $this->offers();
        $offer = isset($filters['offer']) ? ($offers[(string) $filters['offer']] ?? null) : null;

        return $offer === null ? $this->title() : __('offer::booking.profile.requests_title', ['offer' => $offer]);
    }

    public function title(): string
    {
        return __('offer::booking.profile.requests_all');
    }

    /**
     * Ses logements du pays courant, par titre.
     *
     * @return array<string, string>
     */
    private function offers(): array
    {
        $domain = app(DomainRegistry::class)->require();

        return Offer::query()->withTrashed()
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey())
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Offer $offer): array => [(string) $offer->getKey() => $domain->itemTitle($offer) ?? (string) $offer->getTranslation('title', app()->getLocale())])
            ->all();
    }
}
