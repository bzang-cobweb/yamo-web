<?php

namespace Modules\Offer\Forms;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
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
    protected ?Offer $offer = null;

    /** La demande modifiée (`BookingEditForm`), exclue des règles « déjà une demande » ; `null` : une nouvelle. */
    protected ?Booking $editing = null;

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

    protected function type(): BookingTypeEnum
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
                // Un meublé se réserve encore à d'autres dates : seules ses propres demandes qui chevauchent
                // sont refusées (décision 2026-11-21-meuble-plusieurs-reservations).
                ->rules([...$dates, $this->noOwnOverlap()]),
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
     * Un séjour : pas sur des dates où le membre a déjà une demande en attente ou acceptée sur
     * l'offre ; à d'autres dates, oui (décision 2026-11-21-meuble-plusieurs-reservations).
     */
    private function noOwnOverlap(): ValidationRule
    {
        $offer = $this->offer;
        $userId = $this->user?->getAuthIdentifier();
        $except = $this->editing?->getKey();

        return new class($offer, $userId, $except) implements DataAwareRule, ValidationRule
        {
            /** @var array<string, mixed> */
            private array $data = [];

            public function __construct(private readonly ?Offer $offer, private readonly int|string|null $userId, private readonly int|string|null $except) {}

            /** @param  array<string, mixed>  $data */
            public function setData(array $data): static
            {
                $this->data = $data;

                return $this;
            }

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                $out = $this->data['check_out'] ?? null;
                if ($this->offer === null || ! is_string($value) || ! is_string($out)) {
                    return;
                }

                try {
                    [$from, $to] = [Carbon::parse($value), Carbon::parse($out)];
                } catch (\Throwable) {
                    return;
                }

                if ($to->lessThanOrEqualTo($from)) {
                    return;
                }

                if ($this->offer->bookings()
                    ->where('user_id', $this->userId)
                    ->when($this->except !== null, fn ($bookings) => $bookings->whereKeyNot($this->except))
                    ->whereIn('status', [BookingStatusEnum::PENDING, BookingStatusEnum::ACCEPTED])
                    ->overlapping($from, $to)
                    ->exists()) {
                    $fail(__('offer::booking.form.own_overlap'));
                }
            }
        };
    }

    /**
     * Une visite : pas plus d'une demande en attente sur l'offre.
     */
    private function onePending(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->offer !== null && $this->offer->bookings()
                ->where('user_id', $this->user?->getAuthIdentifier())
                ->when($this->editing !== null, fn ($bookings) => $bookings->whereKeyNot($this->editing?->getKey()))
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

    /**
     * Le titre de l'offre, tel que saisi (« Studio meublé à Bonanjo »), sous le titre du formulaire
     * (décision 2026-11-21-titre-offre-reservation).
     */
    public function subtitle(): ?string
    {
        if ($this->offer === null) {
            return null;
        }

        return (string) $this->offer->getTranslation('title', app()->getLocale()) ?: null;
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
