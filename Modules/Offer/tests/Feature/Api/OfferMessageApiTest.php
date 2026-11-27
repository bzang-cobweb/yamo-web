<?php

namespace Modules\Offer\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Models\User;
use Modules\Auth\Sms\SmsSender;
use Modules\Auth\Tests\Concerns\FakeSmsSender;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Lists\OfferCardTitle;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\OfferMessageReceived;
use Tests\TestCase;

/**
 * Écrire à l'éditeur d'une offre, et lui répondre (décision 2026-11-26-messages-editeur) : chaque message est une
 * notification envoyée à l'autre partie, dans le fil `offer_message:{offre}:{membre}`.
 */
class OfferMessageApiTest extends TestCase
{
    use RefreshDatabase;

    private User $publisher;

    private User $member;

    private Offer $offer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
        $this->app->instance(SmsSender::class, new FakeSmsSender);
        $this->publisher = User::factory()->create(['first_name' => 'Paul', 'locale' => 'fr']);
        $this->member = User::factory()->create(['first_name' => 'Awa', 'locale' => 'fr']);
        $this->offer = Offer::factory()->for($this->publisher, 'user')->create([
            'status' => 'published', 'title' => ['fr' => 'Studio Bonapriso'],
            'details' => ['property_type' => 'studio', 'rental_type' => 'furnished'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function api(?User $user, string $method, string $uri, array $data = []): TestResponse
    {
        app('auth')->forgetGuards();
        $this->flushHeaders();
        $request = $this->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM']);
        if ($user !== null) {
            $request = $request->withToken($user->createToken('iPhone')->plainTextToken);
        }

        return $request->json($method, $uri, $data);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function send(User $sender, string $message, array $extra = []): TestResponse
    {
        return $this->api($sender, 'POST', "/api/v1/offers/{$this->offer->getKey()}/messages", ['message' => $message, ...$extra]);
    }

    private function thread(): string
    {
        return 'offer_message:'.$this->offer->getKey().':'.$this->member->getKey();
    }

    /** @return array<string, mixed>|null */
    private function publisherSection(?User $reader): ?array
    {
        return collect($this->api($reader, 'GET', "/api/v1/offers/{$this->offer->getKey()}")->assertOk()->json('data.sections'))->firstWhere('key', 'publisher');
    }

    public function test_the_publisher_section_offers_to_send_a_message_except_to_the_publisher(): void
    {
        $action = ['label' => 'Envoyer un message', 'link' => '/profile/offer.message?record='.$this->offer->getKey()];

        $this->assertSame($action, $this->publisherSection($this->member)['action']);
        // Sans connexion aussi : le formulaire demande la connexion.
        $this->assertSame($action, $this->publisherSection(null)['action']);
        $this->assertNull($this->publisherSection($this->publisher)['action']);
    }

    public function test_a_member_who_already_wrote_finds_the_conversation(): void
    {
        // Décision 2026-11-27-message-vers-conversation : « Envoyer un message » ouvre la page de sa dernière notification
        // du fil, une fois un premier message envoyé ; le formulaire avant.
        $this->assertSame('/profile/offer.message?record='.$this->offer->getKey(), $this->publisherSection($this->member)['action']['link']);
        $this->send($this->member, 'Bonjour')->assertNoContent();
        $this->travel(1)->seconds();
        $this->send($this->member, 'Encore une question')->assertNoContent();

        $latest = DatabaseNotification::query()->where('thread', $this->thread())->latest()->value('id');
        $this->assertSame('/notifications/'.$latest, $this->publisherSection($this->member)['action']['link']);
        // La page s'ouvre pour lui.
        $this->api($this->member, 'GET', '/api/v1/notifications/'.$latest)->assertOk();
        // Un autre membre, qui n'a rien écrit : le formulaire.
        $other = User::factory()->create();
        $this->assertSame('/profile/offer.message?record='.$this->offer->getKey(), $this->publisherSection($other)['action']['link']);
    }

    public function test_the_form_is_served_to_a_member_and_to_the_publisher_replying(): void
    {
        $form = $this->api($this->member, 'GET', '/api/v1/forms/offer.message?record='.$this->offer->getKey())->assertOk()->json('data');
        // Le prénom de l'éditeur en titre (décision 2026-11-27-message-titre-prenom).
        $this->assertSame((string) $this->publisher->first_name, $form['title']);
        // Sans le titre de l'offre (décision 2026-11-26-message-sans-titre-offre).
        $this->assertNull($form['subtitle']);
        $this->assertSame(['message', 'member'], array_column($form['fields'], 'name'));
        $this->assertSame(['textarea', 'hidden'], array_column($form['fields'], 'type'));
        $this->assertSame(1000, $form['fields'][0]['max_length']);
        $this->assertTrue($form['fields'][0]['required']);
        // Sans libellé affiché (décision 2026-11-26-message-sans-libelle).
        $this->assertSame(['Message', true], [$form['fields'][0]['label'], $form['fields'][0]['label_hidden']]);
        $this->assertNull($form['fields'][1]['default']);
        $this->assertSame('POST', $form['submit']['method']);
        $this->assertSame('/v1/offers/'.$this->offer->getKey().'/messages', $form['submit']['endpoint']);
        $this->assertSame(['action' => 'back', 'message' => 'Message envoyé.'], $form['submit']['success']);

        // L'éditeur ne répond qu'après un premier message.
        $reply = '/api/v1/forms/offer.message?record='.$this->offer->getKey().'.'.$this->member->getKey();
        $this->api($this->publisher, 'GET', $reply)->assertForbidden();
        $this->send($this->member, 'Bonjour')->assertNoContent();

        $form = $this->api($this->publisher, 'GET', $reply)->assertOk()->json('data');
        // Le prénom du membre à qui l'éditeur répond.
        $this->assertSame((string) $this->member->first_name, $form['title']);
        // Sans prénom au-dessus du champ (décision 2026-11-27-message-sans-prenom).
        $this->assertNull($form['subtitle']);
        $this->assertSame((string) $this->member->getKey(), $form['fields'][1]['default']);
        // Sa propre offre, sans membre : il s'écrirait à lui-même.
        $this->api($this->publisher, 'GET', '/api/v1/forms/offer.message?record='.$this->offer->getKey())->assertForbidden();
    }

    public function test_a_member_writes_to_the_publisher_who_replies_in_the_same_thread(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $this->send($this->member, '  Le studio est-il libre en décembre ?  ')->assertNoContent();

        $received = $this->publisher->notifications()->sole();
        $this->assertSame(OfferMessageReceived::class, $received->type);
        $this->assertSame($this->thread(), $received->getAttribute('thread'));
        $this->assertSame((int) $this->member->getKey(), (int) $received->getAttribute('user_id'));
        $this->assertSame('Le studio est-il libre en décembre ?', $received->data['body']);
        $this->assertSame('Le studio est-il libre en décembre ?', $received->data['message']);
        $this->assertSame('Message', $received->data['title']);
        $this->assertSame('Écrire', $received->data['link_label']);
        // Un message reçu avant le renommage (« Répondre ») : « Écrire » sur sa page (décision 2026-11-27-bouton-ecrire).
        $received->forceFill(['data' => [...$received->data, 'link_label' => 'Répondre']])->save();
        $this->assertSame('Écrire', $this->api($this->publisher, 'GET', '/api/v1/notifications/'.$received->getKey())->assertOk()->json('data.link_label'));
        // L'éditeur répond à ce membre.
        $this->assertSame('/profile/offer.message?record='.$this->offer->getKey().'.'.$this->member->getKey(), $received->data['link']);
        // La carte montre le message lui-même (décision 2026-11-26-carte-message-texte) ; l'offre est l'en-tête de la page.
        $this->assertSame('Le studio est-il libre en décembre ?', $received->data['summary']);
        $this->assertSame([], $received->data['text_links']);

        // Le membre voit le fil : son message, de lui, et « Répondre » écrit à l'éditeur.
        $cards = $this->api($this->member, 'GET', '/api/v1/notifications')->assertOk()->json('data.items');
        $this->assertCount(1, $cards);
        $page = $this->api($this->member, 'GET', '/api/v1/notifications/'.$cards[0]['id'])->assertOk()->json('data');
        $this->assertSame('/profile/offer.message?record='.$this->offer->getKey(), $page['link']);
        $this->assertSame(['Le studio est-il libre en décembre ?'], array_column($page['history'], 'message'));
        // L'en-tête de l'offre, comme pour une demande (décision 2026-11-26-booking-summary) : son titre vers sa fiche,
        // son titre composé.
        $this->assertSame([
            'title' => 'Studio Bonapriso',
            'subtitle' => OfferCardTitle::of($this->offer, 'fr')[1],
            'link' => '/offers/'.$this->offer->getKey(),
        ], $page['heading']);

        // L'éditeur répond : le membre reçoit la réponse dans le même fil, une seule carte.
        $this->travel(5)->minutes();
        $this->send($this->publisher, 'Oui, à partir du 3.', ['member' => (string) $this->member->getKey()])->assertNoContent();

        $reply = $this->member->notifications()->sole();
        $this->assertSame($this->thread(), $reply->getAttribute('thread'));
        $this->assertSame((int) $this->publisher->getKey(), (int) $reply->getAttribute('user_id'));
        $this->assertSame('/profile/offer.message?record='.$this->offer->getKey(), $reply->data['link']);

        foreach ([$this->member, $this->publisher] as $reader) {
            $cards = $this->api($reader, 'GET', '/api/v1/notifications')->assertOk()->json('data.items');
            $this->assertCount(1, $cards);
            $history = $this->api($reader, 'GET', '/api/v1/notifications/'.$cards[0]['id'])->assertOk()->json('data.history');
            $this->assertSame(['Le studio est-il libre en décembre ?', 'Oui, à partir du 3.'], array_column($history, 'message'));
            $this->assertSame(['Message', 'Message'], array_column($history, 'label'));
            $this->assertSame([(string) $this->member->getKey(), (string) $this->publisher->getKey()], array_column(array_column($history, 'actor'), 'id'));
        }

        // La carte de l'éditeur, sa propre réponse : « Répondre » rouvre la réponse à ce membre.
        $cards = $this->api($this->publisher, 'GET', '/api/v1/notifications')->json('data.items');
        $page = $this->api($this->publisher, 'GET', '/api/v1/notifications/'.$cards[0]['id'])->assertOk()->json('data');
        $this->assertSame('/profile/offer.message?record='.$this->offer->getKey().'.'.$this->member->getKey(), $page['link']);
    }

    public function test_who_may_write(): void
    {
        $stranger = User::factory()->create(['locale' => 'fr']);

        // S'écrire à soi-même, ou écrire le premier en éditeur.
        $this->send($this->publisher, 'Bonjour')->assertForbidden();
        $this->send($this->publisher, 'Bonjour', ['member' => (string) $this->publisher->getKey()])->assertForbidden();
        $this->send($this->publisher, 'Bonjour', ['member' => (string) $this->member->getKey()])->assertForbidden();

        $this->send($this->member, 'Bonjour')->assertNoContent();
        // Un inconnu dans la discussion d'un autre.
        $this->send($stranger, 'Coucou', ['member' => (string) $this->member->getKey()])->assertForbidden();
        $this->api($stranger, 'GET', '/api/v1/forms/offer.message?record='.$this->offer->getKey().'.'.$this->member->getKey())->assertForbidden();
        // L'éditeur ne répond pas à un membre qui ne lui a pas écrit.
        $this->send($this->publisher, 'Bonjour', ['member' => (string) $stranger->getKey()])->assertForbidden();
        // Un membre inconnu.
        $this->send($this->publisher, 'Bonjour', ['member' => '999999'])->assertNotFound();

        // Message vide ou trop long.
        $this->send($this->member, '')->assertUnprocessable()->assertJsonValidationErrors('message');
        $this->send($this->member, str_repeat('a', 1001))->assertUnprocessable()->assertJsonValidationErrors('message');
        // Sans connexion.
        $this->api(null, 'POST', "/api/v1/offers/{$this->offer->getKey()}/messages", ['message' => 'Bonjour'])->assertUnauthorized();

        // Une offre que les autres ne voient pas.
        $this->offer->update(['status' => 'draft']);
        $this->send($stranger, 'Bonjour')->assertNotFound();

        $this->assertSame(1, DatabaseNotification::query()->count());
    }
}
