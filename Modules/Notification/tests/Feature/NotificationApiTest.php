<?php

namespace Modules\Notification\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * L'onglet « Notifications » de l'app (décision 2026-11-19-contrat-centre-notifications) :
 * la liste, le nombre de non lues, marquer lue, tout marquer lu ; seulement les
 * siennes ; effacement au-delà de la durée de conservation.
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    private function notify(User $user, string $title, ?string $readAt = null, ?string $createdAt = null): DatabaseNotification
    {
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'Test',
            'data' => ['title' => $title, 'tag' => ['label' => 'En attente', 'tone' => 'warning'], 'body' => "Texte de {$title}", 'message' => $title === 'Récente' ? 'Bonjour !' : null, 'link' => '/profile/lists/offer.bookings', 'links' => [['label' => "Voir l'annonce", 'link' => '/offers/7'], ['label' => 'Incomplet']], 'text_links' => [['text' => 'Paul', 'link' => '/members/3'], ['text' => 'Sans lien']], 'link_label' => 'Mes réservations'],
            'read_at' => $readAt,
        ]);
        if ($createdAt !== null) {
            $notification->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $notification;
    }

    public function test_the_list_is_newest_first_with_title_text_link_and_read_state(): void
    {
        $user = User::factory()->create();
        $this->notify($user, 'Ancienne', now()->toDateTimeString(), now()->subDay()->toDateTimeString());
        $this->notify($user, 'Récente');
        $this->notify(User::factory()->create(), 'Pas la mienne');

        $items = $this->asUser($user)->getJson('/api/v1/notifications')->assertOk()->json('data.items');

        $this->assertSame(['Récente', 'Ancienne'], array_column($items, 'title'));
        $this->assertSame([false, true], array_column($items, 'read'));
        $this->assertSame('Texte de Récente', $items[0]['body']);
        $this->assertSame('/profile/lists/offer.bookings', $items[0]['link']);
        $this->assertSame(['label' => 'En attente', 'tone' => 'warning'], $items[0]['tag']);
        $this->assertSame('Bonjour !', $items[0]['message']);
        $this->assertSame([['text' => 'Paul', 'link' => '/members/3']], $items[0]['text_links']);
        $this->assertNull($items[1]['message']);
        $this->assertSame([['label' => "Voir l'annonce", 'link' => '/offers/7']], $items[0]['links']);
    }

    public function test_pages_follow_the_cursor(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 25) as $i) {
            $this->notify($user, "N{$i}", null, now()->subMinutes($i)->toDateTimeString());
        }

        $first = $this->asUser($user)->getJson('/api/v1/notifications')->json('data');
        $second = $this->asUser($user)->getJson('/api/v1/notifications?cursor='.urlencode($first['next_cursor']))->json('data');

        $this->assertCount(20, $first['items']);
        $this->assertCount(5, $second['items']);
        $this->assertNull($second['next_cursor']);
    }

    public function test_unread_count_read_one_and_read_all(): void
    {
        $user = User::factory()->create();
        $one = $this->notify($user, 'Une');
        $this->notify($user, 'Deux');
        $count = fn (): int => $this->asUser($user)->getJson('/api/v1/notifications/unread-count')->assertOk()->json('data.unread');

        $this->assertSame(2, $count());
        $this->asUser($user)->postJson("/api/v1/notifications/{$one->getKey()}/read")->assertNoContent();
        $this->assertSame(1, $count());
        $this->asUser($user)->postJson('/api/v1/notifications/read')->assertNoContent();
        $this->assertSame(0, $count());
    }

    public function test_another_account_notification_is_404_and_guests_are_401(): void
    {
        $theirs = $this->notify(User::factory()->create(), 'Pas la mienne');

        $this->asUser(User::factory()->create())->postJson("/api/v1/notifications/{$theirs->getKey()}/read")->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);

        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => ''])->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_notifications_older_than_the_retention_are_pruned(): void
    {
        config(['notification.retention_days' => 30]);
        $user = User::factory()->create();
        $old = $this->notify($user, 'Vieille', null, now()->subDays(31)->toDateTimeString());
        $kept = $this->notify($user, 'Gardée', null, now()->subDays(29)->toDateTimeString());

        $this->artisan('notification:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($kept);
    }

    public function test_one_notification_for_its_page_and_never_another_account_s(): void
    {
        $user = User::factory()->create();
        $mine = $this->notify($user, 'La mienne');
        $other = $this->notify(User::factory()->create(), 'Pas la mienne');

        $this->asUser($user)->getJson("/api/v1/notifications/{$mine->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.title', 'La mienne')
            ->assertJsonPath('data.link_label', 'Mes réservations')
            ->assertJsonPath('data.text_links', [['text' => 'Paul', 'link' => '/members/3']])
            ->assertJsonPath('data.read', false);
        $this->asUser($user)->getJson("/api/v1/notifications/{$other->getKey()}")->assertNotFound();
    }

    public function test_the_card_shows_who_acted_read_live_with_their_online_state(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['first_name' => 'Paul', 'last_name' => 'Etoa', 'last_seen_at' => now(), 'phone' => '+237690000099']);
        $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'Test', 'user_id' => $actor->getKey(),
            'data' => ['title' => 'Demande', 'body' => 'Paul Etoa demande un séjour.', 'summary' => 'demande un séjour.'],
        ]);
        $this->notify($user, 'Sans acteur');

        $items = collect($this->asUser($user)->getJson('/api/v1/notifications')->json('data.items'))->keyBy('title');
        // Sans coordonnées.
        $this->assertSame(['id' => (string) $actor->getKey(), 'name' => 'Paul Etoa', 'initials' => 'PE', 'photo' => null, 'online' => true, 'link' => '/members/'.$actor->getKey()], $items['Demande']['actor']);
        $this->assertSame('demande un séjour.', $items['Demande']['summary']);
        $this->assertNull($items['Sans acteur']['actor']);
        $this->assertNull($items['Sans acteur']['summary']);

        $actor->forceFill(['last_seen_at' => now()->subHour()])->save();
        $id = $items['Demande']['id'];
        $detail = $this->asUser($user)->getJson("/api/v1/notifications/{$id}")->json('data.actor');
        $this->assertFalse($detail['online']);
        // Sur la page de la notification non plus : ni e-mail ni téléphone (décision 2026-11-20-sans-coordonnees).
        $this->assertArrayNotHasKey('email', $detail);
        $this->assertArrayNotHasKey('phone', $detail);
    }

    public function test_deleting_the_account_of_who_acted_keeps_the_notification_without_them(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create();
        $notification = $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'Test', 'user_id' => $actor->getKey(), 'data' => ['title' => 'Demande', 'body' => 'Texte']]);

        $actor->forceDelete();

        $this->assertNull($notification->fresh()->user_id);
        $this->assertNull($this->asUser($user)->getJson("/api/v1/notifications/{$notification->getKey()}")->json('data.actor'));
    }
}
