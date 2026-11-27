<?php

namespace Modules\Partner\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Notifications\DatabaseNotification;
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

        // Un autre membre, qui ne l'a pas reçu, le lit dans le fil : « Message », le texte ; « Écrire » à la place de
        // « Mes partenaires ».
        $page = $this->api($third, 'GET', '/notifications/'.$sent[0]->getKey())->assertOk()->json('data');
        $this->assertSame(['Écrire', '/profile/partner.message?record='.$partner->getKey()], [$page['link_label'], $page['link']]);
        $step = collect($page['history'])->firstWhere('id', (string) $sent[0]->getKey());
        $this->assertSame(['Message', 'On repeint jeudi ?'], [$step['label'], $step['message']]);
    }

    public function test_only_an_attached_account_with_someone_to_read_may_write(): void
    {
        $alone = $this->member();
        $partner = $this->partner([$alone]);

        // Seul membre : personne pour le lire.
        $this->api($alone, 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => 'Bonjour'])->assertForbidden();
        // Un étranger : 404.
        $this->api($this->member(), 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => 'Bonjour'])->assertNotFound();
        // Sans message : 422.
        $partner->users()->attach($this->member(), ['is_admin' => false]);
        $this->api($alone, 'POST', '/partners/'.$partner->getKey().'/messages', ['message' => ''])->assertUnprocessable();
        $this->assertSame(0, DatabaseNotification::query()->where('type', PartnerMessageReceived::class)->count());
    }
}
