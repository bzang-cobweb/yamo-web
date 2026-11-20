<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use LogicException;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Auth\Services\UserAddressService;
use Modules\Location\Models\Country;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Enums\RentalTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferFavorite;
use Modules\Offer\Models\OfferReview;
use Modules\Offer\Services\BookingService;

/**
 * Données de démonstration, en développement seulement : un membre camerounais
 * et 20 logements à son nom (domaine `rental`), chacun avec deux photos dessinées
 * (`DemoPhoto`), et trois visiteurs qui laissent des avis (décision
 * 2026-11-19-contrat-avis-offres), qui lui envoient aussi des demandes : séjours
 * dans les logements meublés, visites des autres, à chaque étape (`BOOKINGS`). Rejouable : rien n'est recréé si le membre a déjà ses offres ;
 * seules les photos manquantes sont ajoutées. Jamais appelé par `DatabaseSeeder`.
 *
 *     ddev artisan db:seed --class='Database\Seeders\DemoSeeder'
 *
 * Compte de démonstration : demo-membre@example.test, mot de passe « password » ;
 * visiteurs : demo-visiteur-1@example.test à -3, même mot de passe. Chacun a un
 * téléphone de démonstration, non vérifié (`PHONES`).
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo-membre@example.test';

    /**
     * Téléphones de démonstration (Cameroun) : le membre, puis les visiteurs 1 à 3.
     * Jamais vérifiés, pour qu'aucun SMS ne parte vers eux.
     */
    public const PHONES = ['+237670000001', '+237670000002', '+237670000003', '+237670000004'];

    /** Mot de passe de démonstration, comme celui des factories. */
    private const PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new LogicException('Données de démonstration refusées en production.');
        }

        $cameroon = Country::query()->where('code', 'CM')->firstOrFail();

        $member = User::query()->firstOrNew(['email' => self::EMAIL]);
        $member->fill([
            'name' => 'demo.membre',
            'first_name' => 'Awa',
            'last_name' => 'Ngono',
            'gender' => GenderEnum::FEMALE,
            'country_id' => $cameroon->getKey(),
            // Un téléphone de démonstration, non vérifié : aucun SMS ne part vers lui.
            'phone' => self::PHONES[0],
            'phone_country_id' => $cameroon->getKey(),
            'locale' => 'fr',
        ]);
        $member->forceFill(['password' => self::PASSWORD, 'email_verified_at' => $member->email_verified_at ?? now()])->save();
        $member->syncRoles([Role::findOrCreate(RoleEnum::MEMBER->value, 'web')]);

        if (Offer::query()->where('user_id', $member->getKey())->count() < 20) {
            $this->offers($member, $cameroon);
        }

        $this->photos($member);
        $this->reviews($member, $cameroon);
        $this->featuredAndFavorites($member);
        $this->amenities($member);
        $this->hours($member);
        $this->bookings($member);
    }

    /**
     * Deux logements en vedette pour 30 jours, et trois favoris du premier visiteur
     * (décision 2026-11-19-contrat-vedette-favoris). Rejouable.
     */
    private function featuredAndFavorites(User $member): void
    {
        $offers = Offer::query()->where('user_id', $member->getKey())->where('status', OfferStatusEnum::PUBLISHED)->orderBy('id')->get()->values();

        foreach ([2, 9] as $index) {
            $offers->get($index)?->update(['featured_until' => now()->addDays(30)]);
        }

        $visitor = User::query()->where('email', 'demo-visiteur-1@example.test')->first();
        foreach ($visitor === null ? [] : [0, 4, 10] as $index) {
            $offer = $offers->get($index);
            if ($offer instanceof Offer) {
                OfferFavorite::query()->firstOrCreate(['user_id' => $visitor->getKey(), 'offer_id' => $offer->getKey()]);
            }
        }
    }

    /**
     * Trois visiteurs et leurs avis sur les logements publiés ; certains logements
     * restent sans avis. Rejouable : un avis par visiteur et par logement.
     */
    private function reviews(User $member, Country $cameroon): void
    {
        $offers = Offer::query()->where('user_id', $member->getKey())->where('status', OfferStatusEnum::PUBLISHED)->orderBy('id')->get()->values();

        foreach (self::REVIEWERS as $number => [$firstName, $lastName]) {
            $reviewer = User::query()->firstOrNew(['email' => 'demo-visiteur-'.($number + 1).'@example.test']);
            $reviewer->fill([
                'name' => 'demo.visiteur'.($number + 1),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => GenderEnum::MALE,
                'country_id' => $cameroon->getKey(),
                'phone' => self::PHONES[$number + 1],
                'phone_country_id' => $cameroon->getKey(),
                'locale' => 'fr',
            ]);
            $reviewer->forceFill(['password' => self::PASSWORD, 'email_verified_at' => $reviewer->email_verified_at ?? now()])->save();
            $reviewer->syncRoles([Role::findOrCreate(RoleEnum::MEMBER->value, 'web')]);

            foreach (self::REVIEWS[$number] as [$offerIndex, $rating, $comment]) {
                $offer = $offers->get($offerIndex);

                if ($offer instanceof Offer) {
                    OfferReview::query()->updateOrCreate(
                        ['offer_id' => $offer->getKey(), 'user_id' => $reviewer->getKey()],
                        ['rating' => $rating, 'comment' => $comment],
                    );
                }
            }
        }
    }

    /**
     * Les demandes des visiteurs au membre, passées par `BookingService` pour que
     * chaque étape soit notifiée et forme le suivi de sa demande (décision
     * 2026-11-20-suivi-demande). Les étapes sont datées des jours passés, dans l'ordre,
     * et notifiées tout de suite (file synchrone). Rejouable : rien si le membre a déjà
     * des demandes.
     */
    private function bookings(User $member): void
    {
        $offers = Offer::query()->where('user_id', $member->getKey())->where('status', OfferStatusEnum::PUBLISHED)->orderBy('id')->get()->values();
        if (Booking::query()->whereIn('offer_id', $offers->modelKeys())->exists()) {
            return;
        }

        $service = app(BookingService::class);
        $queue = config('queue.default');
        config(['queue.default' => 'sync']);
        $today = today();
        $clock = now()->subDays(6)->setTime(9, 0);
        $tick = function () use (&$clock): void {
            $clock = $clock->copy()->addMinutes(97);
            Carbon::setTestNow($clock);
        };

        try {
            $made = [];
            foreach (self::BOOKINGS as $key => [$visitor, $offerIndex, $from, $to, $guests, $message]) {
                $tick();
                $message = $message === self::LONG_MESSAGE ? mb_substr(str_repeat(self::LONG_MESSAGE, 10), 0, Booking::MESSAGE_MAX_LENGTH) : $message;
                $made[$key] = $service->request(
                    User::query()->where('email', 'demo-visiteur-'.$visitor.'@example.test')->firstOrFail(),
                    $offers->get($offerIndex) ?? throw new LogicException("Logement {$offerIndex} absent."),
                    ['check_in' => $today->copy()->addDays($from)->toDateString(), 'check_out' => $to === null ? null : $today->copy()->addDays($to)->toDateString(), 'guests' => $guests, 'message' => $message],
                );
            }

            foreach (self::ANSWERS as [$key, $answer, $note]) {
                $tick();
                $booking = $made[$key]->refresh();
                match ($answer) {
                    'accept' => $service->answer($booking, true),
                    'refuse' => $service->answer($booking, false, $note),
                    'cancel' => $service->cancel($booking),
                };
            }
        } finally {
            Carbon::setTestNow();
            config(['queue.default' => $queue]);
        }
    }

    /**
     * Demandes : visiteur (1 à 3), rang du logement publié, arrivée et départ (ou date
     * et seconde date d'une visite) en jours depuis aujourd'hui, voyageurs, message.
     * Rangs 1, 4, 9, 10, 14 : meublés (séjours) ; 0, 2, 3 : visites.
     *
     * @var array<string, array{int, int, int, int|null, int|null, string|null}>
     */
    private const BOOKINGS = [
        // Le message le plus long admis (`Booking::MESSAGE_MAX_LENGTH`) : voir son rendu partout.
        'studio_pending' => [1, 1, 10, 13, 1, self::LONG_MESSAGE],
        'view_accepted' => [2, 4, 5, 9, 2, 'Nous serons deux, arrivée en soirée.'],
        'view_overlap' => [3, 4, 7, 10, 3, 'Séjour en famille, est-ce possible ?'],
        'flat_refused' => [1, 9, 20, 24, 2, null],
        'short_cancelled' => [2, 10, 15, 17, 1, 'Court séjour pour un rendez-vous.'],
        'upscale_cancelled' => [3, 14, 30, 35, 4, 'Vacances en famille.'],
        'visit_pending' => [1, 0, 3, null, null, 'Je peux passer en fin de journée.'],
        'visit_accepted' => [2, 3, 4, 6, null, 'Je cherche une colocation pour la rentrée.'],
        'visit_refused' => [3, 2, 8, null, null, null],
    ];

    /** Un message répété jusqu'à la longueur maximale d'un message de demande. */
    private const LONG_MESSAGE = 'Bonjour, je viens à Douala pour une mission de travail de trois jours et je cherche un logement calme, proche d’Akwa, avec une bonne connexion internet pour travailler le soir. ';

    /**
     * Les étapes suivantes, dans l'ordre : accepter (les demandes en attente qui
     * chevauchent sont refusées), refuser (avec un motif), annuler (par le visiteur).
     *
     * @var list<array{string, string, string|null}>
     */
    private const ANSWERS = [
        ['view_accepted', 'accept', null],
        ['flat_refused', 'refuse', 'Le logement est en travaux à ces dates.'],
        ['short_cancelled', 'accept', null],
        ['visit_accepted', 'accept', null],
        ['upscale_cancelled', 'cancel', null],
        ['visit_refused', 'refuse', 'La maison vient d’être louée.'],
        ['short_cancelled', 'cancel', null],
    ];

    /**
     * Les 20 logements du membre.
     */
    private function offers(User $member, Country $cameroon): void
    {
        $addresses = $this->addresses($member, $cameroon);
        foreach (self::OFFERS as $index => [$fr, $en, $rentalType, $type, $surface, $rooms, $bedrooms, $price, $charges]) {
            $furnished = $rentalType === RentalTypeEnum::FURNISHED->value;
            $offer = Offer::query()->create([
                'user_id' => $member->getKey(),
                'country_id' => $cameroon->getKey(),
                'title' => ['fr' => $fr, 'en' => $en],
                'description' => [
                    'fr' => 'Logement de démonstration, proche des commerces et des transports.',
                    'en' => 'Demo home, close to shops and transport.',
                ],
                // 16 publiées, 2 brouillons, 2 archivées.
                'status' => match (true) {
                    $index >= 18 => OfferStatusEnum::ARCHIVED,
                    $index >= 16 => OfferStatusEnum::DRAFT,
                    default => OfferStatusEnum::PUBLISHED,
                },
                'price' => $price,
                'currency' => $cameroon->currency,
                'details' => [
                    'property_type' => $type,
                    'rental_type' => $rentalType,
                    'surface' => $surface,
                    'rooms' => $rooms,
                    'bedrooms' => $bedrooms,
                    'available_from' => now()->addDays(7 * ($index % 6))->toDateString(),
                    'charges' => $furnished ? null : $charges,
                    'amenities' => self::AMENITY_SETS[$index % count(self::AMENITY_SETS)],
                ],
            ]);
            $offer->offerAddress()->create(['address_id' => $addresses[$index % count($addresses)]]);
        }
    }

    /**
     * Deux photos dessinées par logement qui n'en a pas encore (rejouable).
     */
    private function photos(User $member): void
    {
        Offer::query()->where('user_id', $member->getKey())->orderBy('id')->get()->each(function (Offer $offer, int $index): void {
            if ($offer->getMedia(Offer::PHOTOS)->isNotEmpty()) {
                return;
            }

            $type = (string) ($offer->detail('property_type') ?? 'apartment');

            foreach ([0, 1] as $shot) {
                $offer->addMediaFromString(DemoPhoto::jpeg($type, $index + $shot * 3))
                    ->usingFileName("demo-{$offer->getKey()}-{$shot}.jpg")
                    ->toMediaCollection(Offer::PHOTOS);
            }
        });
    }

    /**
     * Les adresses du membre à Douala et Yaoundé, créées si besoin.
     *
     * @return list<int> identifiants des adresses partagées
     */
    private function addresses(User $member, Country $cameroon): array
    {
        $service = app(UserAddressService::class);
        $ids = [];

        foreach (self::ADDRESSES as [$street, $zip, $city]) {
            $existing = $member->addresses()->whereHas('address', fn ($query) => $query->where('street', $street))->first();
            $entry = $existing ?? $service->attach($member, [
                'gender' => 'female', 'first_name' => 'Awa', 'last_name' => 'Ngono',
                'country_id' => $cameroon->getKey(), 'street' => $street, 'zip' => $zip, 'city' => $city,
            ]);
            $ids[] = (int) ($entry instanceof UserAddress ? $entry->address_id : 0);
        }

        return $ids;
    }

    /**
     * Équipements des logements, tour à tour (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
     *
     * @var list<list<string>>
     */
    private const AMENITY_SETS = [
        ['wifi', 'parking', 'security'],
        ['wifi', 'air_conditioning', 'hot_water', 'tv'],
        ['parking', 'generator', 'water_tank', 'security'],
        ['wifi', 'equipped_kitchen', 'washing_machine'],
        ['wifi', 'parking', 'air_conditioning', 'generator', 'pool', 'balcony'],
        [],
    ];

    /**
     * Les équipements des logements de démonstration qui n'en ont pas encore (rejouable).
     */
    private function amenities(User $member): void
    {
        Offer::query()->where('user_id', $member->getKey())->orderBy('id')->get()->values()->each(function (Offer $offer, int $index): void {
            if ($offer->detail('amenities') === null) {
                $offer->update(['details' => [...($offer->details ?? []), 'amenities' => self::AMENITY_SETS[$index % count(self::AMENITY_SETS)]]]);
            }
        });
    }

    /**
     * Les heures d'arrivée (14:00) et de départ (11:00) des meublés qui n'en ont pas encore
     * (décision 2026-11-20-heures-arrivee-depart). Rejouable.
     */
    private function hours(User $member): void
    {
        Offer::query()->where('user_id', $member->getKey())->get()->each(function (Offer $offer): void {
            if ($offer->detail('rental_type') === RentalTypeEnum::FURNISHED->value && $offer->detail('check_in_from') === null) {
                $offer->update(['details' => [...($offer->details ?? []), 'check_in_from' => '14:00', 'check_out_until' => '11:00']]);
            }
        });
    }

    /** @var list<array{string, string}> prénom, nom des visiteurs */
    private const REVIEWERS = [
        ['Jean', 'Mbarga'],
        ['Paul', 'Etoa'],
        ['Samuel', 'Fotso'],
    ];

    /**
     * Avis de chaque visiteur : rang du logement publié, note, commentaire.
     *
     * @var list<list<array{int, int, string}>>
     */
    private const REVIEWS = [
        [
            [0, 5, 'Appartement très propre, propriétaire disponible. Je recommande.'],
            [1, 4, 'Studio bien situé à Akwa, un peu bruyant le soir.'],
            [2, 5, 'Grande maison, idéale pour une famille. Quartier calme.'],
            [4, 4, 'Belle vue, appartement conforme aux photos.'],
            [7, 3, 'Chambre correcte mais petite pour le prix.'],
            [10, 5, 'Parfait pour un court séjour, tout est fourni.'],
        ],
        [
            [0, 4, 'Bon rapport qualité-prix à Bonapriso.'],
            [1, 3, 'Coupures d’eau fréquentes, sinon rien à dire.'],
            [3, 4, 'Colocation sympathique, bonne ambiance.'],
            [4, 5, 'Séjour excellent, je reviendrai.'],
            [9, 4, 'Appartement meublé confortable, climatisation efficace.'],
        ],
        [
            [0, 5, 'Accueil chaleureux et logement impeccable.'],
            [2, 4, 'Jardin agréable, cuisine un peu ancienne.'],
            [5, 4, 'Lumineux et pratique, proche des commerces.'],
            [14, 5, 'Haut standing, vraiment au niveau.'],
        ],
    ];

    /** @var list<array{string, string, string}> rue, code, ville */
    private const ADDRESSES = [
        ['Rue Njo-Njo, Bonapriso', '00237', 'Douala'],
        ['Boulevard de la Liberté, Akwa', '00237', 'Douala'],
        ['Rue de Kotto, Bonamoussadi', '00237', 'Douala'],
        ['Avenue Kennedy, Centre', '00237', 'Yaoundé'],
        ['Rue de Bastos', '00237', 'Yaoundé'],
    ];

    /**
     * Titre fr, titre en, type de location, type de logement, surface, pièces, chambres, prix (XAF), charges.
     *
     * @var list<array{string, string, string, string, int, float|int, int, int, int}>
     */
    private const OFFERS = [
        ['Appartement 3 pièces à Bonapriso', '3-room flat in Bonapriso', 'long_term', 'apartment', 85, 3, 2, 250000, 15000],
        ['Studio meublé à Akwa', 'Furnished studio in Akwa', 'furnished', 'studio', 30, 1, 0, 25000, 0],
        ['Maison familiale à Bonamoussadi', 'Family house in Bonamoussadi', 'long_term', 'house', 160, 5, 4, 400000, 25000],
        ['Chambre en colocation à Bastos', 'Room in a flat share in Bastos', 'shared', 'room', 14, 1, 1, 60000, 5000],
        ['Appartement meublé avec vue à Akwa', 'Furnished flat with a view in Akwa', 'furnished', 'apartment', 70, 3, 2, 45000, 0],
        ['Studio lumineux au centre de Yaoundé', 'Bright studio in central Yaoundé', 'long_term', 'studio', 28, 1, 0, 90000, 7000],
        ['Duplex 4 pièces à Bonapriso', '4-room duplex in Bonapriso', 'long_term', 'apartment', 120, 4, 3, 350000, 20000],
        ['Chambre meublée près de l’université', 'Furnished room near the university', 'shared', 'room', 12, 1, 1, 45000, 3000],
        ['Villa avec jardin à Bastos', 'Villa with garden in Bastos', 'long_term', 'house', 220, 6, 4, 650000, 40000],
        ['Appartement meublé 2 pièces à Bonamoussadi', 'Furnished 2-room flat in Bonamoussadi', 'furnished', 'apartment', 50, 2, 1, 35000, 0],
        ['Studio meublé pour courts séjours', 'Furnished studio for short stays', 'furnished', 'studio', 25, 1, 0, 20000, 0],
        ['Appartement 2 pièces à Akwa', '2-room flat in Akwa', 'long_term', 'apartment', 55, 2, 1, 150000, 10000],
        ['Colocation dans une grande maison', 'Flat share in a large house', 'shared', 'room', 16, 1, 1, 55000, 5000],
        ['Maison 3 chambres à Yaoundé', '3-bedroom house in Yaoundé', 'long_term', 'house', 110, 4, 3, 300000, 15000],
        ['Appartement meublé haut standing', 'Upscale furnished flat', 'furnished', 'apartment', 95, 4, 3, 60000, 0],
        ['Studio à Bonamoussadi', 'Studio in Bonamoussadi', 'long_term', 'studio', 25, 1, 0, 80000, 5000],
        ['Appartement 3 pièces en préparation', '3-room flat (draft)', 'long_term', 'apartment', 80, 3, 2, 220000, 12000],
        ['Chambre meublée (brouillon)', 'Furnished room (draft)', 'shared', 'room', 13, 1, 1, 50000, 3000],
        ['Ancien studio meublé (archivé)', 'Former furnished studio (archived)', 'furnished', 'studio', 27, 1, 0, 22000, 0],
        ['Ancienne maison à louer (archivée)', 'Former house for rent (archived)', 'long_term', 'house', 140, 5, 3, 380000, 20000],
    ];
}
