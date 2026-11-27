<?php

namespace Modules\Partner\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Modules\Location\Models\Country;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerMessageReceived;

/**
 * Écrire aux autres membres d'un partenaire, dans son fil (décision 2026-11-27-partenaire-messages) : une seule
 * notification dans l'onglet, à un membre qui n'est pas l'auteur ; le message se lit dans le fil ; « Écrire » à la
 * place de « Mes partenaires » sur la page.
 */
class PartnerMessageTest extends AuthFilamentTestCase
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
     * @param  array<string, mixed>  $data
     */
    private function api(User $user, string $method, string $uri, array $data = []): TestResponse
    {
        Filament::setServingStatus(false);
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])->json($method, '/api/v1'.$uri, $data);
    }

    /**
     * @param  array<int, User>  $admins
     * @param  array<int, User>  $members
     */
    private function partner(array $admins, array $members = []): Partner
    {
        $partner = Partner::factory()->create(['country_id' => $this->cameroon->getKey(), 'name' => ['fr' => 'Peinture Akwa']]);
        foreach ($admins as $admin) {
            $partner->users()->attach($admin, ['is_admin' => true]);
        }
        foreach ($members as $member) {
            $partner->users()->attach($member, ['is_admin' => false]);
        }

        return $partner;
    }

    public function test_one_member_who_is_not_the_author_receives_it_and_everyone_reads_it_in_the_thread(): void
    {
        $author = $this->member();
        $other = $this->member();
        $third = $this->member();
        $partner = $this->partner([$author, $other], [$third]);

        // Le formulaire : le nom du partenaire en titre, le message sans libellé affiché.
        $form = $this->api($author, 'GET', '/forms/partner.message?record='.$partner->getKey())->assertOk()->json('data');
        $this->assertSame(['Peinture Akwa', ['message'], true], [$form['title'], array_column($form['fields'], 'name'), $form['fields'][0]['label_hidden']]);
        $this->assertSame('/v1/partners/'.$partner->getKey().'/messages', $form['submit']['endpoint']);

        $this->api($author, 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => '  On repeint jeudi ?  '])->assertNoContent();

        // Une seule dans l'onglet, au premier autre membre (administrateurs d'abord), de l'auteur, dans le fil.
        $sent = DatabaseNotification::query()->where('type', PartnerMessageReceived::class)->get();
        $this->assertCount(1, $sent);
        $this->assertSame([(int) $other->getKey(), (int) $author->getKey(), $partner->groupThread(), 'On repeint jeudi ?'], [(int) $sent[0]->notifiable_id, (int) $sent[0]->getAttribute('user_id'), $sent[0]->getAttribute('thread'), $sent[0]->data['message']]);

        // Un autre membre, qui ne l'a pas reçu, le lit dans le fil : « Partenaire message », le texte ; « Écrire » à la place de
        // « Mes partenaires ».
        $page = $this->api($third, 'GET', '/notifications/'.$sent[0]->getKey())->assertOk()->json('data');
        $this->assertSame(['Écrire', '/profile/partner.message?record='.$partner->getKey()], [$page['link_label'], $page['link']]);
        $step = collect($page['history'])->firstWhere('id', (string) $sent[0]->getKey());
        $this->assertSame(['Partenaire message', 'On repeint jeudi ?'], [$step['label'], $step['message']]);
        // Sans modification enregistrée : pas de lignes, mais « Dernières modifications » reste au menu, avec son texte vide
        // (décision 2026-11-27-menu-entrees-vides).
        $this->assertNull($page['lines']);
        $this->assertSame(['partner', 'members', 'lines'], array_column($page['menu'], 'key'));
        $this->assertSame('Aucune modification enregistrée.', $page['menu'][2]['empty']);
    }

    public function test_only_the_recipient_is_notified_other_members_get_no_email_or_sms(): void
    {
        // Décision 2026-11-27-partenaire-message-sans-relais.
        Notification::fake();
        $author = $this->member();
        $other = $this->member();
        $third = $this->member();
        $partner = $this->partner([$author, $other, $third]);

        $this->api($author, 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => 'Bonjour'])->assertNoContent();

        Notification::assertSentTo($other, PartnerMessageReceived::class);
        Notification::assertNotSentTo([$author, $third], PartnerMessageReceived::class);
        Notification::assertCount(1);
    }

    public function test_a_lone_member_may_write_the_message_stays_with_them_already_read(): void
    {
        // Décision 2026-11-27-partenaire-message-seul.
        $alone = $this->member();
        $partner = $this->partner([$alone]);

        $this->api($alone, 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => 'Note pour plus tard'])->assertNoContent();
        $kept = DatabaseNotification::query()->where('type', PartnerMessageReceived::class)->sole();
        $this->assertSame([(int) $alone->getKey(), (int) $alone->getKey(), $partner->groupThread()], [(int) $kept->notifiable_id, (int) $kept->getAttribute('user_id'), $kept->getAttribute('thread')]);
        $this->assertNotNull($kept->read_at);
        // Un membre ajouté plus tard le lit dans le fil.
        $later = $this->member();
        $partner->users()->attach($later, ['is_admin' => false]);
        $step = collect($this->api($later, 'GET', '/notifications/'.$kept->getKey())->assertOk()->json('data.history'))->firstWhere('id', (string) $kept->getKey());
        $this->assertSame('Note pour plus tard', $step['message']);
    }

    public function test_only_an_attached_account_may_write_and_a_message_is_required(): void
    {
        $member = $this->member();
        $partner = $this->partner([$member]);

        // Un étranger : 404.
        $this->api($this->member(), 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => 'Bonjour'])->assertNotFound();
        // Sans message : 422.
        $this->api($member, 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => ''])->assertUnprocessable();
        $this->assertSame(0, DatabaseNotification::query()->where('type', PartnerMessageReceived::class)->count());
    }
}
