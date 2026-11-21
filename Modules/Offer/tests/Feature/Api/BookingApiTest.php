<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Notifications\Channels\SmsChannel;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Lists\OfferCardTitle;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;
use Modules\Offer\Notifications\BookingRequested;
use Tests\TestCase;

/**
 * Réservations et visites (décision 2026-11-19-contrat-reservations) : le bouton de
 * la fiche, la demande, la réponse du propriétaire, l'annulation, les listes du
 * profil et les e-mails et SMS.
 */
class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private int $phones = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
        // Qui est prévenu, de quoi, sur quels canaux ; l'envoi réel est testé dans BookingNotificationsTest.
        Notification::fake();
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create(['locale' => 'fr', 'email_verified_at' => now(), ...$attributes]);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function asUser(User $user): static
    {
        // Un autre compte à chaque appel : le garde ne garde pas le précédent.
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);
    }

    private function home(bool $furnished, ?User $owner = null): Offer
    {
        $owner ??= $this->member(['phone' => '+23769000010'.(++$this->phones), 'phone_verified_at' => now()]);

        return Offer::factory()->for($owner, 'user')->create([
            'status' => 'published', 'price' => '45000', 'currency' => 'XAF',
            'details' => ['property_type' => 'studio', 'rental_type' => ($furnished ? 'furnished' : 'long_term')],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stay(array $overrides = []): array
    {
        return ['check_in' => today()->addDays(5)->toDateString(), 'check_out' => today()->addDays(8)->toDateString(), 'guests' => '2', 'message' => 'Bonjour !', ...$overrides];
    }

    public function test_the_offer_page_serves_the_button_by_type_and_never_to_its_owner(): void
    {
        $furnished = $this->home(true);
        $unfurnished = $this->home(false);

        // Un visiteur le voit aussi : l'app le mène d'abord à la connexion.
        $this->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->getJson("/api/v1/offers/{$furnished->getKey()}")
            ->assertJsonPath('data.booking', ['type' => 'stay', 'label' => 'Réserver', 'form' => 'offer.booking']);
        $this->asUser($this->member())->getJson("/api/v1/offers/{$unfurnished->getKey()}")
            ->assertJsonPath('data.booking', ['type' => 'visit', 'label' => 'Demander une visite', 'form' => 'offer.booking']);
        // Son auteur : pas de réservation, mais « Modifier » (décision 2026-11-19-fiche-bouton-modifier).
        $this->asUser($furnished->user)->getJson("/api/v1/offers/{$furnished->getKey()}")
            ->assertJsonPath('data.booking', null)
            ->assertJsonPath('data.edit', ['label' => 'Modifier', 'form' => 'offer.offer']);
        $this->asUser($this->member())->getJson("/api/v1/offers/{$furnished->getKey()}")->assertJsonPath('data.edit', null);
        $this->withHeaders(['X-Country' => 'CM'])->getJson("/api/v1/offers/{$unfurnished->getKey()}")->assertJsonPath('data.edit', null);
    }

    public function test_the_form_asks_dates_and_guests_for_a_stay_and_a_date_for_a_visit(): void
    {
        $guest = $this->member();
        $offer = $this->home(true);
        $stay = $this->asUser($guest)->getJson('/api/v1/forms/offer.booking?record='.$offer->getKey())->assertOk();
        $this->assertSame(['check_in', 'check_out', 'guests', 'message'], array_column($stay->json('data.fields'), 'name'));
        $stay->assertJsonPath('data.title', 'Réserver')
            // Le prix par jour, tel que la fiche l'affiche (espaces insécables compris).
            ->assertJsonPath('data.fields.1.help', app(DomainRegistry::class)->require()->formattedPrice($offer, 'fr').' ; le total figure dans le détail de la réservation une fois la demande envoyée.');

        // Le calendrier : à partir d'aujourd'hui (le départ, de demain), un an au plus (décision 2026-11-19-bornes-des-dates).
        $stay->assertJsonPath('data.fields.0.date_range', ['min' => today()->toDateString(), 'max' => today()->addDays(365)->toDateString()])
            ->assertJsonPath('data.fields.1.date_range.min', today()->addDay()->toDateString());

        $visit = $this->asUser($guest)->getJson('/api/v1/forms/offer.booking?record='.$this->home(false)->getKey())->assertOk();
        // Une visite : sa date, et une seconde date facultative.
        $this->assertSame(['check_in', 'check_out', 'message'], array_column($visit->json('data.fields'), 'name'));
        $visit->assertJsonPath('data.fields.1.label', 'Autre visite (facultatif)')->assertJsonPath('data.fields.1.required', false);
        $visit->assertJsonPath('data.title', 'Demander une visite')->assertJsonPath('data.fields.0.label', 'Date souhaitée');
    }

    public function test_a_stay_request_freezes_the_price_and_tells_the_owner_by_mail_and_sms(): void
    {
        $offer = $this->home(true);
        $guest = $this->member(['first_name' => 'Marie', 'last_name' => 'Curie']);

        $this->asUser($guest)->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $this->stay())->assertNoContent();

        $booking = Booking::query()->sole();
        $this->assertSame(BookingStatusEnum::PENDING, $booking->status);
        $this->assertSame(3, $booking->days());
        $this->assertSame('135000.00', $booking->total);
        $this->assertSame('XAF', $booking->currency);
        $this->assertSame(2, $booking->guests);
        $this->assertSame('Bonjour !', $booking->message);

        // Le propriétaire, par e-mail et SMS (adresse et numéro vérifiés).
        Notification::assertSentTo($offer->user, BookingRequested::class, fn (BookingRequested $notification, array $channels): bool => $notification->step === BookingStepEnum::RECEIVED && $notification->booking->is($booking) && $channels === ['database', 'mail', SmsChannel::class]);
        // Le membre garde sa demande dans son fil, dans l'onglet seulement, déjà lue (décision 2026-11-21-reservation-comme-notification).
        Notification::assertSentTo($guest, BookingRequested::class, fn (BookingRequested $notification, array $channels): bool => $notification->own && $notification->step === BookingStepEnum::RECEIVED && $channels === ['database']);
        Notification::assertCount(2);
    }

    public function test_invalid_requests_are_refused(): void
    {
        $offer = $this->home(true);
        $guest = $this->member();
        $url = "/api/v1/offers/{$offer->getKey()}/bookings";

        $this->asUser($guest)->postJson($url, $this->stay(['check_in' => today()->subDay()->toDateString()]))->assertJsonValidationErrors('check_in');
        $this->asUser($guest)->postJson($url, $this->stay(['check_out' => today()->addDays(5)->toDateString()]))->assertJsonValidationErrors('check_out');
        $this->asUser($guest)->postJson($url, $this->stay(['check_out' => today()->addDays(400)->toDateString()]))->assertJsonValidationErrors('check_out');
        $this->asUser($guest)->postJson($url, $this->stay(['guests' => '0']))->assertJsonValidationErrors('guests');
        // Le nombre de voyageurs est obligatoire pour un séjour (logement meublé).
        $this->asUser($guest)->postJson($url, $this->stay(['guests' => '']))->assertJsonValidationErrors('guests');

        // Un meublé : une autre demande à d'autres dates, oui ; sur des dates qui chevauchent les siennes,
        // non (décision 2026-11-21-meuble-plusieurs-reservations).
        $this->asUser($guest)->postJson($url, $this->stay())->assertNoContent();
        $this->asUser($guest)->postJson($url, $this->stay(['check_in' => today()->addDays(7)->toDateString(), 'check_out' => today()->addDays(10)->toDateString()]))
            ->assertJsonValidationErrors(['check_in' => 'Vous avez déjà une demande pour ce logement sur ces dates.']);
        $this->asUser($guest)->postJson($url, $this->stay(['check_in' => today()->addDays(8)->toDateString(), 'check_out' => today()->addDays(11)->toDateString()]))->assertNoContent();

        // Pas la sienne ; pas une offre non publiée.
        $this->asUser($offer->user)->postJson($url, $this->stay())->assertForbidden();
        $draft = $this->home(true);
        $draft->update(['status' => 'draft']);
        $this->asUser($guest)->postJson("/api/v1/offers/{$draft->getKey()}/bookings", $this->stay())->assertNotFound();
        $this->assertSame(2, Booking::query()->count());
    }

    public function test_the_booking_form_shows_the_offer_title(): void
    {
        // Décision 2026-11-21-titre-offre-reservation.
        $offer = $this->home(true);
        $offer->update(['title' => ['fr' => 'Studio meublé à Bonanjo', 'en' => 'Furnished studio in Bonanjo']]);

        $this->asUser($this->member())->getJson("/api/v1/forms/offer.booking?record={$offer->getKey()}")->assertOk()
            ->assertJsonPath('data.subtitle', 'Studio meublé à Bonanjo');
    }

    public function test_the_guest_edits_a_pending_request_from_its_card(): void
    {
        // Décision 2026-11-21-modifier-reservation.
        $offer = $this->home(true);
        $guest = $this->member();
        $pending = Booking::factory()->for($offer)->for($guest)->create(['check_in' => today()->addDays(5), 'check_out' => today()->addDays(8), 'guests' => 2, 'unit_price' => '45000.00', 'total' => '135000.00']);
        $accepted = Booking::factory()->for($offer)->for($guest)->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today()->addDays(20), 'check_out' => today()->addDays(22)]);

        // « Modifier » dans le menu de la carte, seulement en attente.
        $items = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id');
        $edit = collect($items[(string) $pending->getKey()]['actions'])->firstWhere('label', 'Modifier');
        $this->assertSame(['form' => 'offer.booking.edit', 'record' => (string) $pending->getKey()], $edit['form']);
        $this->assertNull(collect($items[(string) $accepted->getKey()]['actions'])->firstWhere('label', 'Modifier'));

        // Le formulaire, rempli de la demande.
        $form = $this->asUser($guest)->getJson("/api/v1/forms/offer.booking.edit?record={$pending->getKey()}")->assertOk()->json('data');
        $this->assertSame('Modifier la demande', $form['title']);
        $this->assertSame('Enregistrer', $form['submit']['label']);
        $this->assertSame(['method' => 'PUT', 'endpoint' => '/v1/bookings/'.$pending->getKey()], array_intersect_key($form['submit'], ['method' => 1, 'endpoint' => 1]));
        $defaults = array_column($form['fields'], 'default', 'name');
        $this->assertSame([today()->addDays(5)->toDateString(), today()->addDays(8)->toDateString(), '2'], [$defaults['check_in'], $defaults['check_out'], $defaults['guests']]);

        // Ses propres dates ne la gênent pas ; le total suit les nouvelles dates.
        $this->asUser($guest)->putJson('/api/v1/bookings/'.$pending->getKey(), $this->stay(['check_in' => today()->addDays(6)->toDateString(), 'check_out' => today()->addDays(10)->toDateString(), 'guests' => '3']))->assertNoContent();
        $pending->refresh();
        $this->assertSame([today()->addDays(6)->toDateString(), 3, '180000.00'], [$pending->check_in->toDateString(), $pending->guests, $pending->total]);
        // Pas sur les dates de sa demande acceptée.
        $this->asUser($guest)->putJson('/api/v1/bookings/'.$pending->getKey(), $this->stay(['check_in' => today()->addDays(21)->toDateString(), 'check_out' => today()->addDays(23)->toDateString()]))->assertJsonValidationErrors('check_in');

        // Une demande acceptée ne se modifie plus ; celle d'un autre : 404.
        $this->asUser($guest)->putJson('/api/v1/bookings/'.$accepted->getKey(), $this->stay())->assertForbidden();
        $this->asUser($this->member())->putJson('/api/v1/bookings/'.$pending->getKey(), $this->stay())->assertNotFound();
    }

    public function test_the_guest_cancels_from_a_form_with_only_the_message_to_write(): void
    {
        // Décision 2026-11-21-annuler-depuis-le-detail.
        $offer = $this->home(true);
        $guest = $this->member();
        $accepted = Booking::factory()->for($offer)->for($guest)->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today()->addDays(5), 'check_out' => today()->addDays(8), 'guests' => 2, 'message' => 'Bonjour !']);

        $form = $this->asUser($guest)->getJson("/api/v1/forms/offer.booking.cancel?record={$accepted->getKey()}")->assertOk()->json('data');
        $this->assertSame('Annuler la demande', $form['title']);
        $this->assertSame('Confirmer', $form['submit']['label']);
        $this->assertSame(['method' => 'POST', 'endpoint' => '/v1/bookings/'.$accepted->getKey().'/cancel'], array_intersect_key($form['submit'], ['method' => 1, 'endpoint' => 1]));
        $fields = collect($form['fields'])->keyBy('name');
        // Les champs de « Modifier », remplis, en lecture seule ; le message, vide, à écrire.
        $this->assertSame(['check_in', 'check_out', 'guests', 'message'], $fields->keys()->all());
        $this->assertSame([true, true, true, false], $fields->pluck('readonly')->all());
        $this->assertSame(today()->addDays(5)->toDateString(), $fields['check_in']['default']);
        $this->assertNull($fields['message']['default']);

        // Les dates envoyées ne comptent pas ; le message est gardé pour le propriétaire.
        $this->asUser($guest)->postJson('/api/v1/bookings/'.$accepted->getKey().'/cancel', ['check_in' => today()->toDateString(), 'message' => '  Empêchement.  '])->assertNoContent();
        $accepted->refresh();
        $this->assertSame([BookingStatusEnum::CANCELLED, 'Empêchement.', today()->addDays(5)->toDateString()], [$accepted->status, $accepted->cancel_message, $accepted->check_in->toDateString()]);

        // Plus annulable : le formulaire est refusé.
        $this->asUser($guest)->getJson("/api/v1/forms/offer.booking.cancel?record={$accepted->getKey()}")->assertForbidden();
        // Sans message (le menu de la carte) : toujours possible.
        $pending = Booking::factory()->for($offer)->for($guest)->create(['check_in' => today()->addDays(30), 'check_out' => today()->addDays(31)]);
        $this->asUser($guest)->postJson('/api/v1/bookings/'.$pending->getKey().'/cancel')->assertNoContent();
        $this->assertNull($pending->refresh()->cancel_message);
    }

    public function test_a_visit_keeps_one_date_and_no_price(): void
    {
        $offer = $this->home(false);

        $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", ['check_in' => today()->addDays(2)->toDateString()])->assertNoContent();

        $booking = Booking::query()->sole();
        $this->assertNull($booking->check_out);
        $this->assertNull($booking->guests);
        $this->assertNull($booking->total);
    }

    public function test_the_owner_answers_from_received_bookings_and_the_guest_is_told(): void
    {
        $offer = $this->home(true);
        $guest = $this->member(['phone' => '+237690000002', 'phone_verified_at' => now()]);
        $booking = Booking::factory()->for($offer)->for($guest)->create();
        $owner = $offer->user;

        // « Réservations reçues » n'est plus un lien du menu : elle s'ouvre depuis chaque logement
        // (décision 2026-11-19-contrat-reservations-logements-profil).
        $this->assertNotContains('offer.bookings.received', array_column($this->asUser($owner)->getJson('/api/v1/auth/me/menu')->json('data'), 'list'));
        // Sur la carte : « Accepter » (confirmation, envoi) et « Refuser » (formulaire du refus).
        $item = $this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->assertOk()->json('data.items.0');
        // Sa carte à elle (décision 2026-11-21-demandes-d-une-offre) : le statut en pastille orange sur la photo ;
        // sans le logement ; le total en gras ; les actions en menu.
        // Sans photo, le statut au-dessus du titre (décision 2026-11-21-demandes-sans-photo).
        $this->assertSame(['label' => 'En attente', 'tone' => 'warning'], $item['tag']);
        $this->assertNull($item['image']);
        // Après le statut, le nom du membre ; dessous, le type de demande et les dates (décision 2026-11-21-demandes-nom-membre).
        $this->assertSame((string) $guest->full_name, $item['title']);
        // En lien vers sa page (décision 2026-11-21-demandes-lien-membre).
        $this->assertSame('/members/'.$guest->getKey(), $item['title_link']);
        $this->assertSame($booking->type->label().' · '.$booking->period('fr'), $item['subtitle']);
        $this->assertNull($item['rating']);
        // Avec son libellé (décision 2026-11-21-demandes-pastille-non-lues-total).
        $this->assertStringStartsWith('Total : 135', (string) $item['highlight']);
        $this->assertTrue($this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->json('data.menu'));
        [$accept, $refuse] = $item['actions'];
        $this->assertSame(['Accepter', 'primary'], [$accept['label'], $accept['style']]);
        $this->assertStringStartsWith('Accepter la demande de ', (string) $accept['confirm']);
        $this->assertSame(['POST', "/v1/bookings/{$booking->getKey()}/accept"], [$accept['submission']['method'], $accept['submission']['endpoint']]);
        $this->assertSame(['Refuser', 'destructive', ['form' => 'offer.booking.refuse', 'record' => (string) $booking->getKey()]], [$refuse['label'], $refuse['style'], $refuse['form']]);

        // Le formulaire du refus : « refuser » déjà choisi, un message facultatif.
        $fields = array_column($this->asUser($owner)->getJson('/api/v1/forms/offer.booking.refuse?record='.$booking->getKey())->assertOk()->json('data.fields'), null, 'name');
        $this->assertSame(['hidden', 'refuse'], [$fields['answer']['type'], $fields['answer']['default']]);
        $this->assertFalse($fields['refusal_message']['required']);

        // Un autre que le propriétaire : 404.
        $this->asUser($guest)->postJson("/api/v1/bookings/{$booking->getKey()}/answer", ['answer' => 'accept'])->assertNotFound();

        $this->asUser($owner)->postJson("/api/v1/bookings/{$booking->getKey()}/answer", ['answer' => 'refuse', 'refusal_message' => 'Déjà loué.'])->assertNoContent();
        $this->assertSame(BookingStatusEnum::REFUSED, $booking->fresh()->status);
        $this->assertSame('Déjà loué.', $booking->fresh()->refusal_message);
        Notification::assertSentTo($guest, BookingRequested::class, fn (BookingRequested $notification, array $channels): bool => $notification->step === BookingStepEnum::REFUSED && $channels === ['database', 'mail', SmsChannel::class]);

        // Plus en attente : plus de réponse possible, plus de boutons.
        $this->asUser($owner)->postJson("/api/v1/bookings/{$booking->getKey()}/answer", ['answer' => 'accept'])->assertForbidden();
        $this->asUser($owner)->postJson("/api/v1/bookings/{$booking->getKey()}/accept")->assertForbidden();
        // Reste « Masquer l'adresse », quel que soit le statut (décision 2026-11-21-afficher-adresse).
        $this->assertSame(["Masquer l'adresse"], array_column($this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->json('data.items.0.actions'), 'label'));
    }

    public function test_the_guest_follows_and_cancels_their_bookings(): void
    {
        $guest = $this->member();
        $pending = Booking::factory()->for($this->home(true), 'offer')->for($guest)->create();
        $started = Booking::factory()->for($this->home(true), 'offer')->for($guest)
            ->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today(), 'check_out' => today()->addDays(2)]);

        $items = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->assertOk()->json('data.items'))->keyBy('id');
        $this->assertSame('Annuler la demande', $items[(string) $pending->getKey()]['delete']['label']);
        // Sans notification dans son fil : la fiche du logement.
        $this->assertSame('/offers/'.$pending->offer_id, $items[(string) $pending->getKey()]['link']);
        // Avec : la page de sa dernière notification du fil (décision 2026-11-21-reservation-comme-notification).
        $copy = $guest->notifications()->create(['id' => (string) Str::uuid(), 'type' => BookingRequested::class, 'thread' => $pending->thread(), 'read_at' => now(),
            'data' => ['title' => 'Demande', 'body' => 'Texte', 'offer_id' => $pending->offer_id, 'booking_id' => $pending->getKey()]]);
        $this->assertSame('/notifications/'.$copy->getKey(), collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()]['link']);
        // Acceptée et commencée : plus annulable.
        $this->assertNull($items[(string) $started->getKey()]['delete']);
        // Titres inversés (décision 2026-11-21-reservations-titres-inverses-sans-prix).
        $this->assertSame($pending->offer->getTranslation('title', 'fr'), $items[(string) $pending->getKey()]['title']);
        // Sans avis : pas de note (décision 2026-11-21-reservations-note).
        $this->assertNull($items[(string) $pending->getKey()]['rating']);
        OfferReview::factory()->for($pending->offer)->create(['rating' => 4]);
        $rated = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()];
        $this->assertEquals(['average' => 4, 'count' => 1], $rated['rating']);
        // En gras, le prix et son unité, comme à l'accueil (décision 2026-11-21-reservations-prix-comme-accueil).
        $this->assertSame('45 000 FCFA / jour', preg_replace('/[\x{00A0}\x{202F}]/u', ' ', (string) $items[(string) $pending->getKey()]['highlight']));
        // Sous le titre saisi : type de location et type de logement, puis les dates (décision 2026-11-21-reservations-type-de-location-titre).
        $this->assertStringStartsWith("Location meublée · Studio\n", $items[(string) $pending->getKey()]['subtitle']);
        // La ville sur la ligne du type (décision 2026-11-21-reservations-ville-ligne-du-type).
        $pending->offer->offerAddress()->create(['address_id' => Address::factory()->create(['city' => 'Douala'])->getKey()]);
        $card = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()];
        $this->assertSame($pending->offer->getTranslation('title', 'fr'), $card['title']);
        $this->assertStringStartsWith("Location meublée · Studio · Douala\n", $card['subtitle']);
        // Sans le nombre de personnes (décision 2026-11-21-reservations-sans-nombre-personnes).
        $this->assertStringNotContainsString('personne', $items[(string) $pending->getKey()]['subtitle']);
        // Le statut sur la photo ; le menu trois points à la place du cœur (décision 2026-11-21-reservations-menu).
        $this->assertSame(['label' => 'En attente', 'tone' => 'warning', 'on_image' => true], $items[(string) $pending->getKey()]['tag']);
        $this->assertNull($items[(string) $pending->getKey()]['favorite']);
        $this->assertTrue($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.menu'));

        $cancel = $items[(string) $pending->getKey()]['delete'];
        $this->asUser($guest)->json($cancel['method'], '/api'.$cancel['endpoint'])->assertNoContent();
        $this->assertSame(BookingStatusEnum::CANCELLED, $pending->fresh()->status);
        Notification::assertSentTo($pending->offer->user, BookingRequested::class, fn (BookingRequested $notification): bool => $notification->step === BookingStepEnum::CANCELLED);

        $this->asUser($guest)->postJson("/api/v1/bookings/{$started->getKey()}/cancel")->assertForbidden();
        $this->asUser($this->member())->postJson("/api/v1/bookings/{$started->getKey()}/cancel")->assertNotFound();
    }

    public function test_no_mail_or_sms_to_an_unverified_address_or_number(): void
    {
        $owner = $this->member(['email_verified_at' => null, 'phone' => '+237690000003', 'phone_verified_at' => null]);
        $offer = $this->home(true, $owner);

        $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $this->stay())->assertNoContent();

        // Ni e-mail ni SMS : l'onglet « Notifications » seulement (décision 2026-11-19-contrat-centre-notifications).
        Notification::assertSentTo($owner, BookingRequested::class, fn (BookingRequested $notification, array $channels): bool => $notification->step === BookingStepEnum::RECEIVED && $channels === ['database']);
    }

    public function test_accepting_from_the_card_and_the_counter_of_each_home(): void
    {
        // Décision 2026-11-19-contrat-reservations-logements-profil.
        $offer = $this->home(true);
        $owner = $offer->user;
        $other = $this->home(true, $owner);
        $first = Booking::factory()->for($offer)->create(['message' => "Bonjour,\n je viens   pour le travail."]);
        Booking::factory()->for($offer)->create(['message' => null, 'check_in' => today()->addDays(20), 'check_out' => today()->addDays(22)]);
        Booking::factory()->for($other)->status(BookingStatusEnum::REFUSED)->create();

        // Le compteur des demandes en attente, sur la carte du logement.
        $items = collect($this->asUser($owner)->getJson('/api/v1/lists/offer.mine')->json('data.items'))->keyBy('id');
        // Il ouvre les notifications : demandes en attente de ce logement.
        $this->assertSame([
            'label' => '2 demandes en attente',
            'list' => 'notifications',
            'filter' => ['type' => 'booking', 'status' => 'pending', 'offer' => (string) $offer->getKey()],
            'link' => '/notifications/filtered',
            // Orange, comme « En attente » (décision 2026-11-21-pastille-demandes-en-attente).
            'tone' => 'warning',
        ], $items[(string) $offer->getKey()]['counter']);
        $this->assertNull($items[(string) $other->getKey()]['counter']);
        // Le même, premier de la liste des compteurs (décision 2026-11-21-compteur-non-lues).
        $this->assertSame($items[(string) $offer->getKey()]['counter'], $items[(string) $offer->getKey()]['counters'][0]);

        // Ouvert depuis le compteur : les demandes de ce logement, titrées par lui.
        $requests = $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&filter[status]=pending")->json('data');
        // Titrée « Demandes » ; sous les filtres, le titre du logement et sa ligne du type ; sans logement, pas
        // d'intitulé ; sans recherche ; filtres : statut, période, logement caché (décisions
        // 2026-11-21-demandes-titre-sous-filtres et 2026-11-21-demandes-d-une-offre).
        // Le titre du logement dans la barre du haut (décision 2026-11-21-demandes-titre-logement-barre) ; sous les
        // filtres, le nombre de demandes, filtres compris, sans la ligne du type (décision 2026-11-21-demandes-nombre).
        $this->assertSame(OfferCardTitle::of($offer, 'fr')[0], $requests['title']);
        $this->assertSame(['title' => '2 demandes', 'subtitle' => null], $requests['heading']);
        $this->assertSame('Aucune demande', $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&filter[status]=refused")->json('data.heading.title'));
        $all = $this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->json('data');
        $this->assertSame(['Demandes', '3 demandes'], [$all['title'], $all['heading']['title']]);
        // Une recherche sur le nom du membre seul, comptée dans le nombre (décision 2026-11-21-demandes-recherche-nom).
        $this->assertSame('Rechercher un membre…', $requests['search']);
        $this->assertSame([], $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&search=travail")->json('data.items'));
        $found = $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&search=".urlencode((string) $first->user->last_name))->json('data');
        $this->assertContains((string) $first->getKey(), array_column($found['items'], 'id'));
        $this->assertStringEndsWith(count($found['items']) === 1 ? ' demande' : ' demandes', $found['heading']['title']);
        $this->assertSame(['status', 'period', 'offer'], array_column($requests['filters'], 'name'));
        // Le logement, choisi par « Demandes » de sa carte, en champ caché (décision 2026-11-21-demandes-filtre-logement-cache).
        $this->assertSame('hidden', collect($requests['filters'])->keyBy('name')['offer']['type']);
        $this->assertCount(2, $requests['items']);
        $this->assertSame([null, null], array_column($requests['items'], 'quote'));

        // Accepter d'un geste ; un autre que le propriétaire : 404.
        $this->asUser($this->member())->postJson("/api/v1/bookings/{$first->getKey()}/accept")->assertNotFound();
        $this->asUser($owner)->postJson("/api/v1/bookings/{$first->getKey()}/accept")->assertNoContent();
        $this->assertSame(BookingStatusEnum::ACCEPTED, $first->fresh()->status);
        Notification::assertSentTo($first->user, BookingRequested::class, fn (BookingRequested $notification): bool => $notification->step === BookingStepEnum::ACCEPTED);
        $this->assertSame('1 demande en attente', collect($this->asUser($owner)->getJson('/api/v1/lists/offer.mine')->json('data.items'))->keyBy('id')[(string) $offer->getKey()]['counter']['label']);
    }

    public function test_each_home_card_counts_its_unread_notifications_a_request_counting_as_one(): void
    {
        // Décision 2026-11-21-compteur-non-lues.
        $offer = $this->home(true);
        $owner = $offer->user;
        $other = $this->home(true, $owner);
        $notify = fn (Booking $booking, bool $read, ?User $to = null) => ($to ?? $owner)->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => BookingRequested::class, 'thread' => $booking->thread(),
            'read_at' => $read ? now() : null, 'data' => ['title' => 'Demande', 'body' => 'Texte', 'offer_id' => $booking->offer_id, 'booking_id' => $booking->getKey()],
        ]);
        $first = Booking::factory()->for($offer)->create();
        $second = Booking::factory()->for($offer)->create(['check_in' => today()->addDays(20), 'check_out' => today()->addDays(22)]);
        $read = Booking::factory()->for($offer)->create(['check_in' => today()->addDays(30), 'check_out' => today()->addDays(32)]);
        $accepted = Booking::factory()->for($offer)->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today()->addDays(40), 'check_out' => today()->addDays(42)]);
        // Deux étapes non lues d'une même demande, une autre en attente non lue, une acceptée non lue : 3,
        // quel que soit le statut (décision 2026-11-21-compteur-non-lues-sans-statut).
        $notify($first, false);
        $notify($first, false);
        $notify($second, false);
        $notify($accepted, false);
        // Lue : pas comptée.
        $notify($read, true);
        $notify(Booking::factory()->for($other)->status(BookingStatusEnum::REFUSED)->create(), true);
        // Celles d'un autre membre ne comptent pas.
        $notify($first, false, $this->member());

        $items = collect($this->asUser($owner)->getJson('/api/v1/lists/offer.mine')->json('data.items'))->keyBy('id');
        $this->assertSame([[
            'label' => '3 notifications non lues',
            'list' => 'notifications',
            // Demandes de ce logement, non lues, sans filtre de statut.
            'filter' => ['type' => 'booking', 'offer' => (string) $offer->getKey(), 'unread' => 'yes'],
            'link' => '/notifications/filtered',
        ]], array_slice($items[(string) $offer->getKey()]['counters'], 1));
        $this->assertSame([], $items[(string) $other->getKey()]['counters']);

        // Dans « Demandes » du logement, chaque carte compte les siennes, et le compteur ouvre la page de la
        // demande (décision 2026-11-21-demandes-non-lues).
        $cards = collect($this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}")->json('data.items'))->keyBy('id');
        $firstCard = $cards[(string) $first->getKey()];
        // En pastille rouge (décision 2026-11-21-demandes-pastille-non-lues-total).
        $this->assertSame([['label' => '2 notifications non lues', 'list' => 'notifications', 'filter' => [], 'link' => $firstCard['link'], 'tone' => 'danger']], $firstCard['counters']);
        $this->assertStringStartsWith('/notifications/', $firstCard['link']);
        $this->assertSame('1 notification non lue', $cards[(string) $second->getKey()]['counters'][0]['label']);
        $this->assertSame([], $cards[(string) $read->getKey()]['counters']);
    }

    public function test_my_bookings_are_filtered_by_status_city_and_offer_types_with_a_search_button(): void
    {
        $guest = $this->member();
        $pending = Booking::factory()->for($this->home(true), 'offer')->for($guest)->create();
        $upcoming = Booking::factory()->for($this->home(true), 'offer')->for($guest)->status(BookingStatusEnum::ACCEPTED)->create();
        $refused = Booking::factory()->for($this->home(false), 'offer')->for($guest)->visit()->status(BookingStatusEnum::REFUSED)->create();
        $ids = fn (string $query = ''): array => array_column($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings'.$query)->json('data.items'), 'id');

        $data = $this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data');
        $this->assertSame(['label' => 'Rechercher un logement', 'form' => null, 'link' => '/'], $data['footer']);
        // Décision 2026-11-21-reservations-filtre-statut : le statut réel, puis la période, en listes déroulantes.
        $cancelled = Booking::factory()->for($this->home(true), 'offer')->for($guest)->status(BookingStatusEnum::CANCELLED)->create();
        $filters = collect($data['filters'])->keyBy('name');
        $this->assertSame(['status', 'period', 'city', 'offer', 'property_type', 'rental_type'], $filters->keys()->all());
        // Décision 2026-11-21-reservations-filtre-logement : les logements qu'il a demandés, par leur titre.
        $this->assertSame(['Logement', 'select', true], [$filters['offer']['label'], $filters['offer']['type'], $filters['offer']['searchable']]);
        $this->assertSame(array_map('strval', [$pending->offer_id, $upcoming->offer_id, $refused->offer_id]), array_column($filters['offer']['options'], 'value'));
        $this->assertSame([(string) $upcoming->getKey()], $ids('?filter[offer]='.$upcoming->offer_id));
        $this->assertSame(['Statut', 'select'], [$filters['status']['label'], $filters['status']['type']]);
        $this->assertSame(['En attente', 'Acceptée', 'Refusée', 'Annulée'], array_column($filters['status']['options'], 'label'));
        $this->assertSame(['Période', 'select'], [$filters['period']['label'], $filters['period']['type']]);
        $this->assertSame(['upcoming', 'ongoing', 'past'], array_column($filters['period']['options'], 'value'));

        $this->assertSame([(string) $pending->getKey()], $ids('?filter[status]=pending'));
        $this->assertSame([(string) $upcoming->getKey()], $ids('?filter[status]=accepted'));
        $this->assertSame([(string) $refused->getKey()], $ids('?filter[status]=refused'));
        $this->assertSame([(string) $cancelled->getKey()], $ids('?filter[status]=cancelled'));
        // Décision 2026-11-21-reservations-filtre-type : les types réels de l'offre, plus séjour / visite.
        $this->assertSame(['Type de logement', 'select'], [$filters['property_type']['label'], $filters['property_type']['type']]);
        $this->assertSame(['apartment', 'house', 'studio', 'room'], array_column($filters['property_type']['options'], 'value'));
        $this->assertSame(['Type de location', 'select'], [$filters['rental_type']['label'], $filters['rental_type']['type']]);
        $house = $this->home(true);
        $house->update(['details' => [...$house->details, 'property_type' => 'house']]);
        $inHouse = Booking::factory()->for($house, 'offer')->for($guest)->create();
        $house->delete();
        $this->assertSame([(string) $inHouse->getKey()], $ids('?filter[property_type]=house'));
        $this->assertSame([(string) $refused->getKey()], $ids('?filter[rental_type]=long_term'));
        $this->assertSame([], $ids('?filter[property_type]=house&filter[rental_type]=long_term'));
        $this->assertCount(5, $ids('?filter[type]=visit'));
    }

    public function test_my_bookings_period_follows_the_dates_only(): void
    {
        // Décision 2026-11-21-reservations-periode-dates : quel que soit le statut.
        $guest = $this->member();
        $offer = $this->home(true);
        $make = fn (int $in, ?int $out, BookingStatusEnum $status = BookingStatusEnum::ACCEPTED): string => (string) Booking::factory()->for($offer)->for($guest)->status($status)
            ->create(['check_in' => today()->addDays($in), 'check_out' => $out === null ? null : today()->addDays($out)])->getKey();
        $future = $make(5, 7, BookingStatusEnum::CANCELLED);
        $ongoing = $make(-1, 2);
        $leavingToday = $make(-3, 0);
        $arrivingToday = $make(0, 3);
        $done = $make(-6, -2, BookingStatusEnum::PENDING);
        // Une visite : de sa première à sa dernière date proposée.
        $visit = (string) Booking::factory()->for($this->home(false))->for($guest)->visit()->create(['check_in' => today()->addDays(3), 'check_out' => today()->subDay()])->getKey();
        $ids = fn (string $period): array => collect($this->asUser($guest)->getJson("/api/v1/lists/offer.bookings?filter[period]={$period}")->json('data.items'))->pluck('id')->sort()->values()->all();
        $sorted = fn (string ...$keys): array => collect($keys)->sort()->values()->all();

        $filter = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.filters'))->firstWhere('name', 'period');
        $this->assertSame(['À venir', 'En cours', 'Passées'], array_column($filter['options'], 'label'));
        $this->assertSame([$future], $ids('upcoming'));
        $this->assertSame($sorted($ongoing, $leavingToday, $arrivingToday, $visit), $ids('ongoing'));
        $this->assertSame([$done], $ids('past'));
    }

    public function test_my_homes_are_filtered_like_the_home_plus_their_status(): void
    {
        $owner = $this->member();
        $draft = $this->home(true, $owner);
        $draft->update(['status' => 'draft']);
        $this->home(true, $owner);

        $data = $this->asUser($owner)->getJson('/api/v1/lists/offer.mine')->json('data');
        $this->assertSame('status', $data['filters'][0]['name']);
        // Le titre saisi ; dessous, la ligne du type, comme à l'accueil (décision 2026-11-21-cartes-titre-saisi-ligne-type).
        $card = collect($data['items'])->firstWhere('id', (string) $draft->getKey());
        $this->assertSame([$draft->getTranslation('title', 'fr'), 'Location meublée · Studio'], [$card['title'], $card['subtitle']]);
        $this->assertContains('price', array_column($data['filters'], 'name'));
        $this->assertSame([(string) $draft->getKey()], array_column($this->asUser($owner)->getJson('/api/v1/lists/offer.mine?filter[status]=draft')->json('data.items'), 'id'));
    }

    public function test_my_homes_are_filtered_by_the_status_of_their_requests(): void
    {
        $owner = $this->member();
        $pending = $this->home(true, $owner);
        $upcoming = $this->home(true, $owner);
        $quiet = $this->home(true, $owner);
        Booking::factory()->for($pending)->create();
        Booking::factory()->for($upcoming)->status(BookingStatusEnum::ACCEPTED)->create();
        Booking::factory()->for($quiet)->status(BookingStatusEnum::REFUSED)->create();
        $ids = fn (string $group): array => collect($this->asUser($owner)->getJson("/api/v1/lists/offer.mine?filter[booking_status]={$group}")->json('data.items'))->pluck('id')->sort()->values()->all();

        $filter = collect($this->asUser($owner)->getJson('/api/v1/lists/offer.mine')->json('data.filters'))->firstWhere('name', 'booking_status');
        $this->assertSame('Demande', $filter['label']);
        $this->assertSame('select', $filter['type']);
        $this->assertSame(['upcoming', 'pending', 'past'], array_column($filter['options'], 'value'));

        $this->assertSame([(string) $pending->getKey()], $ids('pending'));
        $this->assertSame([(string) $upcoming->getKey()], $ids('upcoming'));
        $this->assertSame([(string) $quiet->getKey()], $ids('past'));
        $this->assertCount(3, $ids(''));
    }
}
