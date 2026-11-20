<?php

namespace Modules\Offer\Forms;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferAvailability;

/**
 * Demander une réservation ou une visite (décision 2026-11-19-contrat-reservations),
 * ouvert depuis la fiche : `?record=` est l'identifiant de l'offre. Un séjour
 * (meublé) : arrivée, départ, voyageurs, message ; une visite (non meublé) : la
 * date souhaitée, message. Une seule demande en attente par membre et par offre.
 * Envoyé à `POST /api/v1/offers/{offer}/bookings`.
 */
class OfferBookingForm extends Form
{
    private ?Offer $offer = null;

    public static function name(): string
    {
        return 'offer.booking';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function forRecord(string $key): static
    {
        $offer = Offer::query()->whereKey($key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('book', $offer);
        $this->offer = $offer;

        return $this;
    }

    private function type(): BookingTypeEnum
    {
        return ($this->offer === null ? null : app(DomainRegistry::class)->require()->bookingType($this->offer)) ?? BookingTypeEnum::VISIT;
    }

    public function fields(): array
    {
        $latest = today()->addDays(Booking::MAX_DAYS)->toDateString();
        $dates = ['date', 'after_or_equal:today', 'before_or_equal:'.$latest];
        $tooFar = __('offer::booking.form.too_far', ['days' => Booking::MAX_DAYS]);
        $message = Field::textarea('message')
            ->label(__('offer::booking.form.message'))
            ->placeholder(__('offer::booking.form.message_placeholder'))
            ->maxLength(Booking::MESSAGE_MAX_LENGTH);

        // Les jours déjà pris ou bloqués, grisés sur la grille (décision 2026-11-19-contrat-calendrier-offre).
        $unavailable = $this->offer === null ? [] : app(OfferAvailability::class)->unavailableDays($this->offer, $this->type(), today(), today()->addDays(Booking::MAX_DAYS + 1));

        if ($this->type() === BookingTypeEnum::VISIT) {
            return [
                Field::date('check_in')
                    ->label(__('offer::booking.form.visit_date'))
                    ->dateRange(today()->toDateString(), $latest)
                    ->calendar($unavailable)
                    ->required()
                    ->rules([...$dates, $this->onePending()]),
                // Une seconde date, facultative, autre que la première.
                Field::date('check_out')
                    ->label(__('offer::booking.form.visit_second_date'))
                    ->dateRange(today()->toDateString(), $latest)
                    ->calendar($unavailable)
                    ->rules(['nullable', ...$dates, 'different:check_in']),
                $message,
            ];
        }

        // Le prix par jour, rappelé sous le départ (« 45 000 FCFA / jour »).
        $price = $this->offer === null ? null : app(DomainRegistry::class)->require()->formattedPrice($this->offer, app()->getLocale());

        return [
            Field::date('check_in')
                ->label(__('offer::booking.form.check_in'))
                ->dateRange(today()->toDateString(), $latest)
                ->calendar($unavailable)
                ->required()
                ->rules([...$dates, $this->onePending()]),
            Field::date('check_out')
                ->label(__('offer::booking.form.check_out'))
                ->dateRange(today()->addDay()->toDateString(), $latest)
                ->calendar($unavailable, after: 'check_in')
                ->help($price === null ? null : __('offer::booking.form.price_help', ['price' => $price]))
                ->required()
                ->rules(['date', 'after:check_in', $this->withinMaxDays($tooFar)]),
            $this->guests(__('offer::booking.form.guests')),
            $message,
        ];
    }

    /**
     * Le nombre de voyageurs d'un séjour (logement meublé), obligatoire, 1 au départ.
     */
    private function guests(string $label): Field
    {
        return Field::number('guests')
            ->label($label)
            ->default('1')
            ->required()
            ->rules(['required', 'integer', 'min:1', 'max:'.Booking::MAX_GUESTS]);
    }

    /**
     * Pas plus d'une demande en attente sur l'offre.
     */
    private function onePending(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->offer !== null && $this->offer->bookings()
                ->where('user_id', $this->user?->getAuthIdentifier())
                ->where('status', BookingStatusEnum::PENDING)
                ->exists()) {
                $fail(__('offer::booking.form.pending_exists'));
            }
        };
    }

    /**
     * Un départ au plus `MAX_DAYS` jours après aujourd'hui.
     */
    private function withinMaxDays(string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($message): void {
            try {
                $date = Carbon::parse((string) $value);
            } catch (\Throwable) {
                return;
            }
            if ($date->greaterThan(today()->addDays(Booking::MAX_DAYS))) {
                $fail($message);
            }
        };
    }

    public function title(): string
    {
        return __('offer::booking.form.'.$this->type()->value.'_title');
    }

    public function subtitle(): ?string
    {
        if ($this->offer === null) {
            return null;
        }

        return app(DomainRegistry::class)->require()->itemTitle($this->offer) ?? (string) $this->offer->getTranslation('title', app()->getLocale());
    }

    /**
     * Sans offre (`?record=` absent), aucun envoi.
     */
    public function submission(): ?Submission
    {
        return $this->offer === null ? null : Submission::toRoute(
            'api.v1.offers.bookings.store',
            SuccessAction::BACK,
            __('offer::booking.form.sent_'.$this->type()->value),
            parameters: ['offer' => $this->offer->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('offer::booking.form.submit_'.$this->type()->value);
    }
}
