<?php

namespace Modules\Partner\Tests\Feature;

use App\Models\Setting;
use Filament\Facades\Filament;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Filament\Resources\PartnerResource\Pages\EditPartner;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerPending;
use Modules\Partner\Notifications\PartnerPublished;
use Modules\Partner\Services\PartnerService;

/**
 * Le fil des notifications d'un partenaire, lu par tous ses comptes ; une notification par action : au compte du
 * back-office de la configuration pour une demande depuis l'app, à l'auteur de la demande pour une action au
 * back-office, jamais à celui qui agit (décision 2026-11-24-notifications-fil-de-groupe).
 */
class PartnerThreadTest extends AuthFilamentTestCase
{
    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cameroon = Country::factory()->create(['code' => 'CM']);
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    /**
     * Un appel de l'app, hors du back-office.
     *
     * @param  array<string, mixed>  $data
     */
    private function api(User $user, string $method, string $uri, array $data = []): TestResponse
    {
        Filament::setServingStatus(false);
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->json($method, '/api/v1'.$uri, $data);
    }

    public function test_an_edit_records_only_what_changed_and_an_edit_without_change_notifies_nobody(): void
    {
        // Décision 2026-11-26-partenaire-modifications.
        $team = User::factory()->create(['locale' => 'fr']);
        Setting::current()->update(['partner_notify_user_id' => $team->getKey()]);
        $author = $this->member();
        $fields = ['name' => 'Déménageurs du Wouri', 'slogan' => 'Vos meubles en sécurité.', 'description' => 'Texte.'];
        $this->api($author, 'POST', '/partners', $fields)->assertNoContent();
        $partner = Partner::query()->where('name->fr', 'Déménageurs du Wouri')->sole();

        $this->travel(1)->minutes();
        // Le slogan retiré, le lien ajouté : deux changements ; le nom et la description, pareils, n'y sont pas.
        $this->api($author, 'PATCH', "/partners/{$partner->getKey()}", [...$fields, 'slogan' => '', 'website' => 'https://wouri.test'])->assertNoContent();
        // Gardée sur le partenaire, avec qui l'a faite (décision 2026-11-26-partenaire-dernieres-modifications).
        $partner->refresh();
        $this->assertSame([
            ['field' => 'slogan', 'old' => 'Vos meubles en sécurité.', 'new' => ''],
            ['field' => 'website', 'old' => '', 'new' => 'https://wouri.test'],
        ], $partner->last_changes['changes']);
        $this->assertSame($author->getKey(), $partner->last_changes['by']);
        $edit = $team->notifications()->latest()->first();
        $this->assertArrayNotHasKey('changes', $edit->data);
        // La page de la notification les montre en lignes, sous l'en-tête.
        $this->assertSame([
            ['key' => 'slogan', 'label' => 'Slogan', 'value' => '« Vos meubles en sécurité. » → (vide)'],
            ['key' => 'website', 'label' => 'Lien', 'value' => '(vide) → « https://wouri.test »'],
        ], $this->api($author, 'GET', '/notifications/'.$this->api($author, 'GET', '/notifications')->json('data.items.0.id'))->json('data.lines'));

        // Rien de changé : aucune notification de plus.
        $this->api($author, 'PATCH', "/partners/{$partner->getKey()}", [...$fields, 'slogan' => '', 'website' => 'https://wouri.test'])->assertNoContent();
        $this->assertSame(2, $team->notifications()->count());
    }

    public function test_a_partner_thread_is_read_by_all_its_accounts(): void
    {
        $team = User::factory()->create(['locale' => 'fr']);
        Setting::current()->update(['partner_notify_user_id' => $team->getKey()]);
        $author = $this->member();
        $this->api($author, 'POST', '/partners', ['name' => 'Déménageurs du Wouri', 'slogan' => 'Vos meubles en sécurité.', 'description' => 'Texte.'])->assertNoContent();
        $partner = Partner::query()->where('name->fr', 'Déménageurs du Wouri')->sole();
        $helper = $this->member();
        $partner->users()->attach($helper->getKey(), ['is_admin' => false]);
        $stranger = $this->member();

        // Ajouté par l'auteur, modifié par l'autre compte, accepté, modifié de nouveau. Une minute entre les étapes.
        $this->travel(1)->minutes();
        $this->api($helper, 'PATCH', "/partners/{$partner->getKey()}", ['name' => 'Déménageurs du Wouri', 'slogan' => 'Vos meubles en sécurité.', 'description' => 'Autre texte.'])->assertNoContent();
        // L'équipe valide au back-office (l'action « Valider » appelle `accept()`, testée dans PartnerResourceTest).
        $this->travel(1)->minutes();
        Filament::setServingStatus();
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        app(PartnerService::class)->accept($partner->fresh());
        $this->travel(1)->minutes();
        $this->api($author, 'PATCH', "/partners/{$partner->getKey()}", ['name' => 'Déménageurs du Wouri', 'slogan' => 'Vos meubles en sécurité.', 'description' => 'Encore.'])->assertNoContent();

        // Une notification par action : trois au compte du back-office, la réponse à l'auteur de la dernière demande.
        $this->assertSame(3, $team->notifications()->count());
        $this->assertSame(['Partenaire accepté'], $helper->notifications()->pluck('data')->pluck('title')->all());
        $this->assertSame(0, $author->notifications()->count());
        $this->assertSame($partner->groupThread(), $team->notifications()->value('thread'));

        // Chaque compte du partenaire lit tout son fil, une carte, quel que soit le destinataire (décision
        // 2026-11-24-notifications-fil-de-groupe), rédigé pour lui.
        $labels = ['Partenaire ajouté', 'Partenaire modifié', 'Partenaire accepté', 'Partenaire modifié'];
        foreach ([$author, $helper] as $reader) {
            $cards = $this->api($reader, 'GET', '/notifications')->assertOk()->json('data.items');
            $this->assertCount(1, $cards);
            $page = $this->api($reader, 'GET', '/notifications/'.$cards[0]['id'])->assertOk()->json('data');
            $this->assertSame($labels, array_column($page['history'], 'label'));
            // Sous l'en-tête, la dernière modification du partenaire, gardée sur lui (décision
            // 2026-11-26-partenaire-dernieres-modifications) ; les étapes du suivi gardent leur libellé.
            $this->assertSame([['key' => 'description', 'label' => 'Description', 'value' => '« Autre texte. » → « Encore. »']], $page['lines']);
            $this->assertSame([null, null, null, null], array_column($page['history'], 'message'));
            // L'en-tête du partenaire : son nom vers sa page, son slogan dessous (décision 2026-11-26-entete-partenaire).
            $this->assertSame([
                'title' => (string) $partner->getTranslation('name', 'fr'),
                'subtitle' => 'Vos meubles en sécurité.',
                'link' => '/partners/'.$partner->getKey(),
            ], $page['heading']);
        }
        // L'en-tête de l'auteur : l'étape de sa carte, sa dernière modification, dans ses mots (décision
        // 2026-11-24-entete-etape-ouverte).
        $cards = $this->api($author, 'GET', '/notifications')->json('data.items');
        $this->assertSame('Votre partenaire « Déménageurs du Wouri » a été modifié.', $this->api($author, 'GET', '/notifications/'.$cards[0]['id'])->json('data.body'));
        // Rien de reçu : rien de non lu ; l'autre compte a reçu la réponse.
        $this->assertTrue($cards[0]['read']);
        $this->assertSame(1, $this->api($helper, 'GET', '/notifications/unread-count')->json('data.unread'));
        // Le filtre « Non lues » dit la même chose (décision 2026-11-24-filtre-non-lues-lecteur) : le fil n'est non lu
        // que pour celui qui en a reçu une notification qu'il n'a pas lue.
        $this->assertSame([], $this->api($author, 'GET', '/notifications?filter[unread]=yes')->json('data.items'));
        $this->assertCount(1, $this->api($author, 'GET', '/notifications?filter[unread]=no')->json('data.items'));
        $this->assertCount(1, $this->api($helper, 'GET', '/notifications?filter[unread]=yes')->json('data.items'));

        // Le compte du back-office ne lit que ce qu'il a reçu ; un étranger, rien.
        $cards = $this->api($team, 'GET', '/notifications')->json('data.items');
        $this->assertSame(['Partenaire ajouté', 'Partenaire modifié', 'Partenaire modifié'], array_column($this->api($team, 'GET', '/notifications/'.$cards[0]['id'])->json('data.history'), 'label'));
        $this->assertSame([], $this->api($stranger, 'GET', '/notifications')->json('data.items'));
        $this->api($stranger, 'GET', '/notifications/'.$cards[0]['id'])->assertNotFound();
    }

    public function test_the_group_threads_of_a_reader_are_read_once_per_request(): void
    {
        // Une page de notifications lit les partenaires du lecteur une fois, malgré la sous-requête de `latestOnly()`.
        $member = $this->member();
        foreach ([1, 2] as $index) {
            Partner::factory()->for($this->cameroon)->create()->users()->attach($member->getKey(), ['is_admin' => true]);
        }
        $queries = 0;
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            if (str_contains($query->sql, 'partner_user')) {
                $queries++;
            }
        });

        $this->api($member, 'GET', '/notifications')->assertOk();
        $this->assertSame(1, $queries);
    }

    public function test_back_office_edits_notify_the_author_but_not_the_back_office_user(): void
    {
        $team = $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        $admin = $this->member();
        $helper = $this->member();
        $partner = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Meubles Akwa'], 'status' => PartnerStatusEnum::PUBLISHED]);
        $partner->users()->attach([$admin->getKey() => ['is_admin' => true], $helper->getKey() => ['is_admin' => false], $team->getKey() => ['is_admin' => true]]);
        // La dernière demande depuis l'app : celle de l'autre compte.
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(), 'type' => PartnerPending::class, 'notifiable_type' => $team->getMorphClass(), 'notifiable_id' => $team->getKey(),
            'user_id' => $helper->getKey(), 'thread' => $partner->groupThread(), 'data' => ['title' => 'Partenaire modifié', 'body' => 'Texte'],
        ]);
        Notification::fake();

        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['name' => ['fr' => 'Meubles Akwa Plus']])
            ->call('save')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($helper, PartnerPublished::class, function (PartnerPublished $notification) use ($helper, $partner): bool {
            $data = $notification->toArray($helper);

            return $data['title'] === 'Partenaire modifié'
                && $data['body'] === "Votre partenaire « Meubles Akwa Plus » a été modifié par l'équipe."
                && $notification->notificationThread($helper) === $partner->groupThread()
                && $notification->notificationUserId($helper) === null;
        });
        // Ses administrateurs : e-mail et SMS seulement ; l'utilisateur du back-office, administrateur lui aussi, rien.
        Notification::assertSentTo($admin, PartnerPublished::class, fn ($notification, array $channels): bool => ! in_array('database', $channels, true));
        Notification::assertNotSentTo($team, PartnerPublished::class);

        // Un changement de statut à l'enregistrement : la seule notification de statut, pas « modifié » en plus.
        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['status' => PartnerStatusEnum::REJECTED->value])
            ->call('save')
            ->assertHasNoFormErrors();
        Notification::assertSentToTimes($helper, PartnerPublished::class, 1);
        Notification::assertSentTo($helper, PartnerStatusEnum::REJECTED->notification(), fn ($notification): bool => $notification->toArray($helper)['title'] === 'Partenaire refusé');
        Notification::assertSentTo($admin, PartnerStatusEnum::REJECTED->notification(), fn ($notification, array $channels): bool => ! in_array('database', $channels, true));
        Notification::assertNotSentTo($team, PartnerStatusEnum::REJECTED->notification());
    }
}
