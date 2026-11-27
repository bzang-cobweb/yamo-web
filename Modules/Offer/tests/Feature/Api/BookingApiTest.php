<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
use Modules\Offer\Http\Resources\BookingResource;
use Modules\Offer\Lists\OfferCardTitle;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestCancelled;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\BookingRequestRefused;
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
            'status' => 'published', 'price' => '45000',
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
        // Le titre composé, sous le titre (décision 2026-11-22-fiche-titres-centres).
        $this->asUser($this->member())->getJson("/api/v1/offers/{$furnished->getKey()}")->assertJsonPath('data.composed_title', OfferCardTitle::of($furnished, 'fr')[1]);
        // « Retirer », pour son auteur seulement, à la place du prix (décision 2026-11-22-retirer-depuis-la-fiche).
        $this->asUser($this->member())->getJson("/api/v1/offers/{$furnished->getKey()}")->assertJsonPath('data.delete', null);
        $delete = $this->asUser($furnished->user)->getJson("/api/v1/offers/{$furnished->getKey()}")->json('data.delete');
        $this->assertSame(['Retirer', 'Retirer cette offre ?', 'DELETE', "/v1/offers/{$furnished->getKey()}", true, ['action' => 'back', 'message' => 'Offre retirée.']], [$delete['label'], $delete['confirm'], $delete['method'], $delete['endpoint'], $delete['destructive'], $delete['success']]);
        $this->withHeaders(['X-Country' => 'CM'])->getJson("/api/v1/offers/{$unfurnished->getKey()}")->assertJsonPath('data.edit', null);
    }

    public function test_today_is_the_day_of_the_offer_country(): void
    {
        // Décision 2026-11-25-fuseau-par-pays : à 23:30 UTC, il est déjà 00:30 le lendemain à Douala.
        Country::query()->where('code', 'CM')->update(['timezone' => 'Africa/Douala']);
        $this->travelTo(Carbon::parse('2026-07-11 23:30:00', 'UTC'));
        $offer = $this->home(true);

        $this->asUser($this->member())->getJson('/api/v1/forms/offer.booking?record='.$offer->getKey())
            ->assertJsonPath('data.fields.0.range.min', '2026-07-12')
            ->assertJsonPath('data.fields.1.range.min', '2026-07-13');
        // Le 11, déjà passé au Cameroun, est refusé.
        $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $this->stay(['check_in' => '2026-07-11', 'check_out' => '2026-07-14']))
            ->assertJsonValidationErrors('check_in');
        $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $this->stay(['check_in' => '2026-07-12', 'check_out' => '2026-07-14']))
            ->assertNoContent();
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
        $stay->assertJsonPath('data.fields.0.range', ['min' => today()->toDateString(), 'max' => today()->addDays(365)->toDateString(), 'step' => null])
            ->assertJsonPath('data.fields.1.range.min', today()->addDay()->toDateString());

        $visit = $this->asUser($guest)->getJson('/api/v1/forms/offer.booking?record='.$this->home(false)->getKey())->assertOk();
        // Une seule date de visite (décision 2026-11-22-visite-une-seule-date).
        $this->assertSame(['check_in', 'message'], array_column($visit->json('data.fields'), 'name'));
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
        Notification::assertSentTo($offer->user, BookingRequestReceived::class, fn (BookingRequestReceived $notification, array $channels): bool => $notification->step === BookingStepEnum::RECEIVED && $notification->booking->is($booking) && $channels === ['database', 'mail', SmsChannel::class]);
        // Une seule notification : le membre en est l'auteur et la retrouve dans son fil sans la recevoir (décision
        // 2026-11-24-notifications-fil-de-groupe).
        Notification::assertNotSentTo($guest, BookingRequestReceived::class);
        Notification::assertCount(1);
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

    public function test_the_edit_and_cancel_forms_name_the_request_type(): void
    {
        // Décision 2026-11-26-formulaires-titre-type : « Modifier la demande de visite », « Annuler la demande de visite ».
        $guest = $this->member();
        $visit = Booking::factory()->for($this->home(false))->for($guest)->visit()->create(['check_in' => today()->addDays(5)]);

        $this->assertSame('Modifier la demande de visite', $this->asUser($guest)->getJson("/api/v1/forms/offer.booking.edit?record={$visit->getKey()}")->assertOk()->json('data.navigation_title'));
        $this->assertSame('Annuler la demande de visite', $this->asUser($guest)->getJson("/api/v1/forms/offer.booking.cancel?record={$visit->getKey()}")->assertOk()->json('data.navigation_title'));
    }

    public function test_the_guest_edits_a_pending_request_from_its_card(): void
    {
        // Décision 2026-11-21-modifier-reservation.
        $offer = $this->home(true);
        $guest = $this->member();
        $pending = Booking::factory()->for($offer)->for($guest)->create(['check_in' => today()->addDays(5), 'check_out' => today()->addDays(8), 'guests' => 2, 'unit_price' => '45000.00', 'total' => '135000.00']);
        $accepted = Booking::factory()->for($offer)->for($guest)->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today()->addDays(20), 'check_out' => today()->addDays(22)]);

        // Plus de « Modifier » sur la carte : il est sur la page de la demande (décision 2026-11-22-reservations-coeur).
        $items = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id');
        $this->assertSame([], $items[(string) $pending->getKey()]['actions']);

        // Le formulaire, rempli de la demande.
        $form = $this->asUser($guest)->getJson("/api/v1/forms/offer.booking.edit?record={$pending->getKey()}")->assertOk()->json('data');
        // L'action dans la barre du haut ; ni titre ni sous-titre dans le formulaire (décision 2026-11-27-modifier-annuler-sans-titre).
        $this->assertSame(['Modifier la demande de séjour', null, null], [$form['navigation_title'], $form['title'], $form['subtitle']]);
        $pending->offer->setTranslations('title', [])->save();
        $this->assertNull((new BookingResource($pending->fresh()))->resolve(request())['composed_title']);
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
        // L'action dans la barre du haut ; ni titre ni sous-titre (décision 2026-11-27-modifier-annuler-sans-titre).
        $this->assertSame(['Annuler la demande de séjour', null, null], [$form['navigation_title'], $form['title'], $form['subtitle']]);
        $this->assertSame('Confirmer', $form['submit']['label']);
        $this->assertSame(['method' => 'POST', 'endpoint' => '/v1/bookings/'.$accepted->getKey().'/cancel'], array_intersect_key($form['submit'], ['method' => 1, 'endpoint' => 1]));
        // Le message de réussite dit le type de la demande (décision 2026-11-26-titres-type-de-demande).
        $this->assertSame('Demande de séjour annulée.', $form['submit']['success']['message']);
        $fields = collect($form['fields'])->keyBy('name');
        // Les champs de « Modifier », remplis, en lecture seule ; le message, vide, à écrire.
        $this->assertSame(['check_in', 'check_out', 'guests', 'message'], $fields->keys()->all());
        $this->assertSame([true, true, true, false], $fields->pluck('readonly')->all());
        $this->assertTrue($fields['message']['required']);
        $this->assertSame(today()->addDays(5)->toDateString(), $fields['check_in']['default']);
        $this->assertNull($fields['message']['default']);

        // Les dates envoyées ne comptent pas ; le message est gardé pour le propriétaire.
        $this->asUser($guest)->postJson('/api/v1/bookings/'.$accepted->getKey().'/cancel', ['check_in' => today()->toDateString(), 'message' => '  Empêchement.  '])->assertNoContent();
        $accepted->refresh();
        $this->assertSame([BookingStatusEnum::CANCELLED, 'Empêchement.', today()->addDays(5)->toDateString()], [$accepted->status, $accepted->cancel_message, $accepted->check_in->toDateString()]);

        // Plus annulable : le formulaire est refusé.
        $this->asUser($guest)->getJson("/api/v1/forms/offer.booking.cancel?record={$accepted->getKey()}")->assertForbidden();
        // Sans message : refusée, la demande reste en attente (décision 2026-11-26-annulation-message-obligatoire).
        $pending = Booking::factory()->for($offer)->for($guest)->create(['check_in' => today()->addDays(30), 'check_out' => today()->addDays(31)]);
        $this->asUser($guest)->postJson('/api/v1/bookings/'.$pending->getKey().'/cancel')->assertUnprocessable()->assertJsonValidationErrors('message');
        $this->asUser($guest)->postJson('/api/v1/bookings/'.$pending->getKey().'/cancel', ['message' => '   '])->assertUnprocessable()->assertJsonValidationErrors('message');
        $this->assertSame(BookingStatusEnum::PENDING, $pending->refresh()->status);
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
        // Sans total (décision 2026-11-22-demandes-sans-total).
        $this->assertNull($item['highlight']);
        // Ni menu ni bouton : la carte ouvre le détail de la demande (décision 2026-11-22-demandes-carte-seule) ; on
        // répond depuis le calendrier, son bouton « Traiter la demande ».
        $this->assertFalse($this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->json('data.menu'));
        $this->assertSame([], $item['actions']);
        $day = fn (): array => $this->asUser($owner)->getJson("/api/v1/offers/{$offer->getKey()}/calendar/{$booking->check_in->toDateString()}")->assertOk()->json('data.items.0.actions');
        [$accept, $refuse] = $day();
        $this->assertSame(['Accepter', 'primary'], [$accept['label'], $accept['style']]);
        // « Accepter » ouvre son formulaire, comme « Refuser » (décision 2026-11-22-accepter-avec-message).
        $this->assertSame([null, null, ['form' => 'offer.booking.accept', 'record' => (string) $booking->getKey()]], [$accept['confirm'], $accept['submission'], $accept['form']]);
        $accepting = $this->asUser($owner)->getJson('/api/v1/forms/offer.booking.accept?record='.$booking->getKey())->assertOk()->json('data');
        $this->assertSame('Accepter la demande', $accepting['navigation_title']);
        $this->assertSame(['answer', 'acceptance_message'], array_column($accepting['fields'], 'name'));
        $this->assertSame(['POST', "/v1/bookings/{$booking->getKey()}/answer"], [$accepting['submit']['method'], $accepting['submit']['endpoint']]);
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
        Notification::assertSentTo($guest, BookingRequestRefused::class, fn (BookingRequestRefused $notification, array $channels): bool => $notification->step === BookingStepEnum::REFUSED && $channels === ['database', 'mail', SmsChannel::class]);

        // Plus en attente : plus de réponse possible, plus de boutons.
        $this->asUser($owner)->postJson("/api/v1/bookings/{$booking->getKey()}/answer", ['answer' => 'accept'])->assertForbidden();
        $this->asUser($owner)->postJson("/api/v1/bookings/{$booking->getKey()}/accept")->assertForbidden();
        // Refusée : plus dans le panneau du jour ; la carte de la liste, elle, n'a jamais de bouton
        // (décision 2026-11-22-demandes-carte-seule).
        $this->assertSame([], $this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->json('data.items.0.actions'));
    }

    public function test_the_guest_follows_and_cancels_their_bookings(): void
    {
        $guest = $this->member();
        $pending = Booking::factory()->for($this->home(true), 'offer')->for($guest)->create();
        $started = Booking::factory()->for($this->home(true), 'offer')->for($guest)
            ->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today(), 'check_out' => today()->addDays(2)]);

        $items = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->assertOk()->json('data.items'))->keyBy('id');
        // Plus de menu trois points : le cœur des favoris du logement, éteint ; « Annuler » est sur la page de la demande
        // (décision 2026-11-22-reservations-coeur).
        $this->assertFalse($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.menu'));
        $this->assertSame([], $items[(string) $pending->getKey()]['actions']);
        $this->assertFalse($items[(string) $pending->getKey()]['favorite']['active']);
        $pending->offer->favorites()->create(['user_id' => $guest->getKey()]);
        $this->assertTrue(collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()]['favorite']['active']);
        $this->assertNull($items[(string) $pending->getKey()]['delete']);
        // Sans notification dans son fil : la fiche du logement.
        $this->assertSame('/offers/'.$pending->offer_id, $items[(string) $pending->getKey()]['link']);
        // À droite des étoiles, un œil vers la fiche de l'offre ; les étoiles ouvrent ses avis dans un panneau ; le titre de
        // l'offre n'est plus un lien (décision 2026-11-27-reservations-oeil-voir).
        $card = $items[(string) $pending->getKey()];
        $this->assertSame('/offers/'.$pending->offer_id, $card['view_link']);
        $this->assertSame(['label' => 'Avis', 'panel' => ['name' => 'offer.reviews', 'record' => (string) $pending->offer_id]], $card['rating_panel']);
        $this->assertSame((string) $pending->offer->getTranslation('title', 'fr'), $card['subtitle']);
        $this->assertArrayNotHasKey('subtitle_link', $card);
        $this->assertArrayNotHasKey('title_link', $card);
        // Sa demande, reçue par le propriétaire et pas encore répondue : la page de la demande, par cette notification
        // dont il est l'auteur (décision 2026-11-24-reservations-lien-demande-envoyee).
        $sent = $pending->offer->user->notifications()->create(['id' => (string) Str::uuid(), 'type' => BookingRequestReceived::class, 'thread' => $pending->thread(), 'user_id' => $guest->getKey(),
            'data' => ['title' => 'Nouvelle demande', 'body' => 'Texte', 'offer_id' => $pending->offer_id, 'booking_id' => $pending->getKey()]]);
        $this->assertSame('/notifications/'.$sent->getKey(), collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()]['link']);
        // Pas lue par le propriétaire, elle ne compte pas parmi les siennes non lues.
        $this->assertSame([], collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()]['counters']);
        $this->travel(1)->minutes();
        // Avec une notification reçue, plus récente : la page de sa dernière notification du fil (décision
        // 2026-11-21-reservation-comme-notification).
        $copy = $guest->notifications()->create(['id' => (string) Str::uuid(), 'type' => BookingRequestReceived::class, 'thread' => $pending->thread(), 'read_at' => now(),
            'data' => ['title' => 'Demande', 'body' => 'Texte', 'offer_id' => $pending->offer_id, 'booking_id' => $pending->getKey()]]);
        $this->assertSame('/notifications/'.$copy->getKey(), collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()]['link']);
        // Lue : pas de compteur ; non lue : « 1 notification non lue », vers la page de la demande (décision
        // 2026-11-22-reservations-non-lues).
        $this->assertSame([], collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()]['counters']);
        $copy->update(['read_at' => null]);
        $card = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()];
        $this->assertSame([['label' => '1 notification non lue', 'list' => 'notifications', 'filter' => [], 'link' => '/notifications/'.$copy->getKey()]], $card['counters']);
        $copy->update(['read_at' => now()]);
        // Acceptée et commencée : plus annulable, plus de « Annuler ».
        $this->assertNotContains('Annuler', array_column($items[(string) $started->getKey()]['actions'], 'label'));
        // En titre, la ligne du type ; dessous, le titre saisi puis la demande (décision 2026-11-21-cartes-titre-compose-en-tete).
        [$cardTitle, $cardLine] = OfferCardTitle::composedFirst($pending->offer, 'fr');
        $this->assertSame($cardTitle, $items[(string) $pending->getKey()]['title']);
        // Le sous-titre de l'accueil, le titre saisi ; le type de demande et les dates sur leur ligne (décision
        // 2026-11-22-reservations-ligne-dates).
        $this->assertSame($pending->offer->getTranslation('title', 'fr'), $items[(string) $pending->getKey()]['subtitle']);
        $this->assertSame($pending->type->label().' · '.$pending->period('fr'), $items[(string) $pending->getKey()]['detail']);
        // Sans avis : pas de note (décision 2026-11-21-reservations-note).
        $this->assertNull($items[(string) $pending->getKey()]['rating']);
        OfferReview::factory()->for($pending->offer)->create(['rating' => 4]);
        $rated = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()];
        $this->assertEquals(['average' => 4, 'count' => 1], $rated['rating']);
        // En gras, le prix et son unité, comme à l'accueil (décision 2026-11-21-reservations-prix-comme-accueil).
        $this->assertSame('45 000 FCFA / jour', preg_replace('/[\x{00A0}\x{202F}]/u', ' ', (string) $items[(string) $pending->getKey()]['highlight']));
        // En titre : type de location et type de logement ; dessous le titre saisi, puis les dates (décisions
        // 2026-11-21-reservations-type-de-location-titre et 2026-11-21-cartes-titre-compose-en-tete).
        $this->assertSame('Location meublée · Studio', $items[(string) $pending->getKey()]['title']);
        // La ville sur la ligne du type (décision 2026-11-21-reservations-ville-ligne-du-type).
        $pending->offer->offerAddress()->create(['address_id' => Address::factory()->create(['city' => 'Douala'])->getKey()]);
        $card = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.items'))->keyBy('id')[(string) $pending->getKey()];
        $this->assertSame('Location meublée · Studio · Douala', $card['title']);
        $this->assertSame($pending->offer->getTranslation('title', 'fr'), $card['subtitle']);
        // Sans le nombre de personnes (décision 2026-11-21-reservations-sans-nombre-personnes).
        $this->assertStringNotContainsString('personne', $items[(string) $pending->getKey()]['detail']);
        // Le statut sur la photo ; le cœur des favoris à la place du menu trois points (décision 2026-11-22-reservations-coeur).
        $this->assertSame(['label' => 'En attente', 'tone' => 'warning', 'on_image' => true], $items[(string) $pending->getKey()]['tag']);
        $this->assertNotNull($items[(string) $pending->getKey()]['favorite']);
        // Le nombre, sous les filtres, filtres compris (décision 2026-11-22-listes-nombre-en-tete).
        $this->assertSame('2 réservations', $this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.heading.title'));
        $this->assertSame('1 réservation', $this->asUser($guest)->getJson('/api/v1/lists/offer.bookings?filter[status][]=pending')->json('data.heading.title'));

        $this->asUser($guest)->postJson("/api/v1/bookings/{$pending->getKey()}/cancel", ['message' => 'Empêchement.'])->assertNoContent();
        $this->assertSame(BookingStatusEnum::CANCELLED, $pending->fresh()->status);
        Notification::assertSentTo($pending->offer->user, BookingRequestCancelled::class, fn (BookingRequestCancelled $notification): bool => $notification->step === BookingStepEnum::CANCELLED);

        $this->asUser($guest)->postJson("/api/v1/bookings/{$started->getKey()}/cancel", ['message' => 'Empêchement.'])->assertForbidden();
        $this->asUser($this->member())->postJson("/api/v1/bookings/{$started->getKey()}/cancel")->assertNotFound();
    }

    public function test_no_mail_or_sms_to_an_unverified_address_or_number(): void
    {
        $owner = $this->member(['email_verified_at' => null, 'phone' => '+237690000003', 'phone_verified_at' => null]);
        $offer = $this->home(true, $owner);

        $this->asUser($this->member())->postJson("/api/v1/offers/{$offer->getKey()}/bookings", $this->stay())->assertNoContent();

        // Ni e-mail ni SMS : l'onglet « Notifications » seulement (décision 2026-11-19-contrat-centre-notifications).
        Notification::assertSentTo($owner, BookingRequestReceived::class, fn (BookingRequestReceived $notification, array $channels): bool => $notification->step === BookingStepEnum::RECEIVED && $channels === ['database']);
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
        $requests = $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&filter[status][]=pending")->json('data');
        // Titrée « Demandes » ; sous les filtres, le titre du logement et sa ligne du type ; sans logement, pas
        // d'intitulé ; sans recherche ; filtres : statut, période, logement caché (décisions
        // 2026-11-21-demandes-titre-sous-filtres et 2026-11-21-demandes-d-une-offre).
        // Le titre du logement dans la barre du haut (décision 2026-11-21-demandes-titre-logement-barre) ; sous les
        // filtres, le nombre de demandes, filtres compris, sans la ligne du type (décision 2026-11-21-demandes-nombre).
        $this->assertSame(OfferCardTitle::of($offer, 'fr')[0], $requests['title']);
        $this->assertSame(['title' => '2 demandes reçues', 'subtitle' => null], $requests['heading']);
        $this->assertSame('Aucune demande reçue', $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&filter[status][]=refused")->json('data.heading.title'));
        $all = $this->asUser($owner)->getJson('/api/v1/lists/offer.bookings.received')->json('data');
        $this->assertSame(['Demandes', '3 demandes reçues'], [$all['title'], $all['heading']['title']]);
        // Une recherche sur le nom du membre seul, comptée dans le nombre (décision 2026-11-21-demandes-recherche-nom).
        $this->assertSame('Rechercher…', $requests['search']);
        $this->assertSame([], $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&search=travail")->json('data.items'));
        $found = $this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}&search=".urlencode((string) $first->user->last_name))->json('data');
        $this->assertContains((string) $first->getKey(), array_column($found['items'], 'id'));
        $this->assertStringEndsWith(count($found['items']) === 1 ? ' demande reçue' : ' demandes reçues', $found['heading']['title']);
        $this->assertSame(['status', 'period', 'offer'], array_column($requests['filters'], 'name'));
        // Le logement, choisi par « Demandes » de sa carte, en champ caché (décision 2026-11-21-demandes-filtre-logement-cache).
        $this->assertSame('hidden', collect($requests['filters'])->keyBy('name')['offer']['type']);
        $this->assertCount(2, $requests['items']);
        // Le dernier message, sa première ligne ; sans message, rien (décision 2026-11-22-demandes-dernier-message).
        $quotes = collect($requests['items'])->pluck('quote', 'id');
        $this->assertSame('Bonjour,', $quotes[(string) $first->getKey()]);
        $this->assertNull($quotes->except((string) $first->getKey())->first());

        // Accepter d'un geste ; un autre que le propriétaire : 404.
        $this->asUser($this->member())->postJson("/api/v1/bookings/{$first->getKey()}/accept")->assertNotFound();
        $this->asUser($owner)->postJson("/api/v1/bookings/{$first->getKey()}/accept")->assertNoContent();
        $this->assertSame(BookingStatusEnum::ACCEPTED, $first->fresh()->status);
        Notification::assertSentTo($first->user, BookingRequestAccepted::class, fn (BookingRequestAccepted $notification): bool => $notification->step === BookingStepEnum::ACCEPTED);
        $this->assertSame('1 demande en attente', collect($this->asUser($owner)->getJson('/api/v1/lists/offer.mine')->json('data.items'))->keyBy('id')[(string) $offer->getKey()]['counter']['label']);
    }

    public function test_each_home_card_counts_its_unread_notifications_a_request_counting_as_one(): void
    {
        // Décision 2026-11-21-compteur-non-lues.
        $offer = $this->home(true);
        $owner = $offer->user;
        $other = $this->home(true, $owner);
        $notify = fn (Booking $booking, bool $read, ?User $to = null) => ($to ?? $owner)->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => BookingRequestReceived::class, 'thread' => $booking->thread(),
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

        // Dans « Demandes » du logement, plus de pastille des non lues (décision 2026-11-22-demandes-sans-pastille-non-lues).
        $cards = collect($this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}")->json('data.items'))->keyBy('id');
        $firstCard = $cards[(string) $first->getKey()];
        $this->assertSame([], $firstCard['counters']);
        // À la place, le nombre en pastille à droite du message (décision 2026-11-22-demandes-pastille-nombre-message).
        $this->assertSame(2, $firstCard['unread_count']);
        $this->assertSame(1, $cards[(string) $second->getKey()]['unread_count']);
        $this->assertArrayNotHasKey('unread_count', $cards[(string) $read->getKey()]);
        $this->assertStringStartsWith('/notifications/', $firstCard['link']);

        // Le dernier message du fil, d'un côté ou de l'autre, sa première ligne (décision 2026-11-22-demandes-dernier-message).
        $this->member()->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => BookingRequestReceived::class, 'thread' => $second->thread(), 'read_at' => now(),
            'data' => ['title' => 'Demande', 'body' => 'Texte', 'offer_id' => $second->offer_id, 'booking_id' => $second->getKey(), 'message' => "Arrivée vers 18 h.\nMerci !"],
        ]);
        $cards = collect($this->asUser($owner)->getJson("/api/v1/lists/offer.bookings.received?filter[offer]={$offer->getKey()}")->json('data.items'))->keyBy('id');
        $this->assertSame('Arrivée vers 18 h.', $cards[(string) $second->getKey()]['quote']);
        // La date de sa dernière notification (décision 2026-11-22-demandes-date-derniere-notification) ; sans, rien.
        $this->assertNotNull($cards[(string) $first->getKey()]['date'] ?? null);
        $this->assertSame(Carbon::parse($cards[(string) $first->getKey()]['date'])->toIso8601String(), $cards[(string) $first->getKey()]['date']);
    }

    public function test_my_bookings_are_filtered_by_status_city_and_offer_types_with_a_search_button(): void
    {
        $guest = $this->member();
        $pending = Booking::factory()->for($this->home(true), 'offer')->for($guest)->create();
        $upcoming = Booking::factory()->for($this->home(true), 'offer')->for($guest)->status(BookingStatusEnum::ACCEPTED)->create();
        $refused = Booking::factory()->for($this->home(false), 'offer')->for($guest)->visit()->status(BookingStatusEnum::REFUSED)->create();
        $ids = fn (string $query = ''): array => array_column($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings'.$query)->json('data.items'), 'id');

        $data = $this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data');
        $this->assertSame(['label' => 'Rechercher un logement', 'form' => null, 'link' => '/search'], $data['footer']);
        // Décision 2026-11-21-reservations-filtre-statut : le statut réel, puis la période, en listes déroulantes.
        $cancelled = Booking::factory()->for($this->home(true), 'offer')->for($guest)->status(BookingStatusEnum::CANCELLED)->create();
        $filters = collect($data['filters'])->keyBy('name');
        // La région avant la ville (décision 2026-11-25-region-de-l-adresse).
        $this->assertSame(['status', 'period', 'property_type', 'rental_type', 'region', 'city', 'offer'], $filters->keys()->all());
        // Décision 2026-11-21-reservations-filtre-logement : les logements qu'il a demandés, par leur titre.
        $this->assertSame(['Logement', 'select', true], [$filters['offer']['label'], $filters['offer']['type'], $filters['offer']['searchable']]);
        $this->assertSame(array_map('strval', [$pending->offer_id, $upcoming->offer_id, $refused->offer_id]), array_column($filters['offer']['options'], 'value'));
        // Chaque logement avec sa photo devant son titre ; sans photo, pas d'image (décision 2026-11-27-select-images).
        $this->assertArrayNotHasKey('image', $filters['offer']['options'][0]);
        Storage::fake('public');
        $pending->offer->addMedia(UploadedFile::fake()->image('salon.jpg'))->toMediaCollection(Offer::PHOTOS);
        $photos = collect($this->asUser($guest)->getJson('/api/v1/lists/offer.bookings')->json('data.filters'))->keyBy('name')['offer']['options'];
        $this->assertStringContainsString('salon', (string) collect($photos)->firstWhere('value', (string) $pending->offer_id)['image']);
        $this->assertArrayNotHasKey('image', $filters['status']['options'][0]);
        $this->assertArrayNotHasKey('image', $filters['status']['options'][0]);
        // Le logement se choisit une région choisie (décision 2026-11-26-ville-et-logement-apres-region) : sans région,
        // il est masqué et ignoré.
        $this->assertCount(4, $ids('?filter[offer]='.$upcoming->offer_id));
        $there = Booking::factory()->for(Offer::factory()->at(Address::factory()->for(Country::query()->where('code', 'CM')->sole())->create(['city' => 'Douala']))->create(['status' => 'published']), 'offer')->for($guest)->create();
        $this->assertSame([(string) $there->getKey()], $ids('?filter[region][]='.$there->offer->address->region_id.'&filter[offer]='.$there->offer_id));
        $there->delete();
        // La ville et le logement, seulement une région choisie (décision 2026-11-26-ville-et-logement-apres-region).
        $this->assertSame([array_column($filters['region']['options'], 'value'), array_column($filters['region']['options'], 'value')], [$filters['city']['visible_when']['values'], $filters['offer']['visible_when']['values']]);
        // En puces (décision 2026-11-26-reservations-filtres-en-puces).
        $this->assertSame(['Statut', 'chips'], [$filters['status']['label'], $filters['status']['type']]);
        $this->assertSame(['En attente', 'Acceptée', 'Refusée', 'Annulée', 'Terminée', 'Expirée'], array_column($filters['status']['options'], 'label'));
        $this->assertSame(['Période', 'chips'], [$filters['period']['label'], $filters['period']['type']]);
        $this->assertSame(['upcoming', 'ongoing', 'past'], array_column($filters['period']['options'], 'value'));

        $this->assertSame([(string) $pending->getKey()], $ids('?filter[status][]=pending'));
        $this->assertSame([(string) $upcoming->getKey()], $ids('?filter[status][]=accepted'));
        $this->assertSame([(string) $refused->getKey()], $ids('?filter[status][]=refused'));
        // Plusieurs statuts : les demandes de l'un d'eux (décision 2026-11-26-reservations-filtres-en-puces).
        $this->assertEqualsCanonicalizing([(string) $pending->getKey(), (string) $refused->getKey()], $ids('?filter[status][]=pending&filter[status][]=refused'));
        $this->assertSame([(string) $cancelled->getKey()], $ids('?filter[status][]=cancelled'));
        // Décision 2026-11-21-reservations-filtre-type : les types réels de l'offre, plus séjour / visite.
        // « Type » (décision 2026-11-25-libelle-type).
        $this->assertSame(['Type', 'chips'], [$filters['property_type']['label'], $filters['property_type']['type']]);
        $this->assertSame(['apartment', 'house', 'studio', 'room'], array_column($filters['property_type']['options'], 'value'));
        $this->assertSame(['Type de location', 'chips'], [$filters['rental_type']['label'], $filters['rental_type']['type']]);
        $house = $this->home(true);
        $house->update(['details' => [...$house->details, 'property_type' => 'house']]);
        $inHouse = Booking::factory()->for($house, 'offer')->for($guest)->create();
        $house->delete();
        $this->assertSame([(string) $inHouse->getKey()], $ids('?filter[property_type][]=house'));
        $this->assertSame([(string) $refused->getKey()], $ids('?filter[rental_type][]=long_term'));
        $this->assertSame([], $ids('?filter[property_type][]=house&filter[rental_type][]=long_term'));
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
        $ids = fn (string $period): array => collect($this->asUser($guest)->getJson("/api/v1/lists/offer.bookings?filter[period][]={$period}")->json('data.items'))->pluck('id')->sort()->values()->all();
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
        // En titre, la ligne du type ; dessous, le titre saisi, comme à l'accueil (décision 2026-11-21-cartes-titre-compose-en-tete).
        $card = collect($data['items'])->firstWhere('id', (string) $draft->getKey());
        $this->assertSame(['Location meublée · Studio', $draft->getTranslation('title', 'fr')], [$card['title'], $card['subtitle']]);
        $this->assertContains('price', array_column($data['filters'], 'name'));
        $this->assertSame([(string) $draft->getKey()], array_column($this->asUser($owner)->getJson('/api/v1/lists/offer.mine?filter[status][]=draft')->json('data.items'), 'id'));
    }

    public function test_my_homes_are_filtered_by_the_status_of_their_requests(): void
    {
        // Décision 2026-11-22-logements-filtre-statut-demande.
        $owner = $this->member();
        $homes = [];
        foreach (['pending', 'accepted', 'refused', 'cancelled', 'expired', 'completed'] as $status) {
            $homes[$status] = $this->home(true, $owner);
            Booking::factory()->for($homes[$status])->status(BookingStatusEnum::from($status))->create(['check_in' => today()->addDays(10), 'check_out' => today()->addDays(12)]);
        }
        // Acceptée mais passée : terminée, pas acceptée (décision 2026-11-22-logements-filtre-terminee).
        $over = $this->home(true, $owner);
        Booking::factory()->for($over)->status(BookingStatusEnum::ACCEPTED)->create(['check_in' => today()->subDays(5), 'check_out' => today()->subDays(2)]);
        // En attente mais passée : expirée, pas en attente.
        $late = $this->home(true, $owner);
        Booking::factory()->for($late)->create(['check_in' => today()->subDays(3), 'check_out' => today()->subDay()]);
        $ids = fn (string $status): array => collect($this->asUser($owner)->getJson("/api/v1/lists/offer.mine?filter[booking_status][]={$status}")->json('data.items'))->pluck('id')->sort()->values()->all();

        $filter = collect($this->asUser($owner)->getJson('/api/v1/lists/offer.mine')->json('data.filters'))->firstWhere('name', 'booking_status');
        // En puces (décision 2026-11-27-statuts-en-puces).
        $this->assertSame(['Demande', 'chips'], [$filter['label'], $filter['type']]);
        $this->assertSame(['pending', 'accepted', 'refused', 'cancelled', 'expired', 'completed'], array_column($filter['options'], 'value'));
        $this->assertSame(['En attente', 'Acceptée', 'Refusée', 'Annulée', 'Expirée', 'Terminée'], array_column($filter['options'], 'label'));

        foreach (['pending', 'accepted', 'refused', 'cancelled'] as $status) {
            $this->assertSame([(string) $homes[$status]->getKey()], $ids($status));
        }
        $this->assertSame(collect([$homes['expired'], $late])->map(fn ($home): string => (string) $home->getKey())->sort()->values()->all(), $ids('expired'));
        $this->assertSame(collect([$homes['completed'], $over])->map(fn ($home): string => (string) $home->getKey())->sort()->values()->all(), $ids('completed'));
        $this->assertCount(8, $ids(''));
        // Plusieurs statuts : les logements qui ont une demande dans l'un d'eux (décision 2026-11-27-statuts-en-puces).
        $this->assertSame(
            collect([$homes['pending'], $homes['refused']])->map(fn ($home): string => (string) $home->getKey())->sort()->values()->all(),
            $ids('pending&filter[booking_status][]=refused'),
        );
    }
}
