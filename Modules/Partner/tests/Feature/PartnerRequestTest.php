<?php

namespace Modules\Partner\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
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
use Modules\Partner\Models\PartnerRequest;
use Modules\Partner\Notifications\PartnerPublished;
use Modules\Partner\Services\PartnerService;

/**
 * Les demandes d'un partenaire et le fil de leurs notifications ; au back-office, seuls ses administrateurs sont
 * prévenus, jamais celui qui agit (décision 2026-11-24-partenaire-demandes).
 */
class PartnerRequestTest extends AuthFilamentTestCase
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

    public function test_a_request_gathers_its_steps_in_one_thread_per_recipient(): void
    {
        $author = $this->member();
        $this->api($author, 'POST', '/partners', ['name' => 'Déménageurs du Wouri', 'description' => 'Texte.'])->assertNoContent();
        $partner = Partner::query()->where('name->fr', 'Déménageurs du Wouri')->sole();
        $helper = $this->member();
        $partner->users()->attach($helper->getKey(), ['is_admin' => false]);

        // Une demande d'ajout, en validation ; une modification tant qu'elle l'est la prolonge. Une minute entre les
        // étapes : elles se suivent dans l'ordre.
        $this->travel(1)->minutes();
        $this->api($helper, 'PATCH', "/partners/{$partner->getKey()}", ['name' => 'Déménageurs du Wouri', 'description' => 'Autre texte.'])->assertNoContent();
        $request = PartnerRequest::query()->sole();
        $this->assertSame([PartnerRequest::CREATED, PartnerStatusEnum::PENDING, $author->getKey()], [$request->action, $request->status, $request->user_id]);

        // L'équipe valide au back-office (l'action « Valider » appelle `accept()`, testée dans PartnerResourceTest) : la
        // demande reçoit sa réponse.
        $this->travel(1)->minutes();
        Filament::setServingStatus();
        $team = $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        app(PartnerService::class)->accept($partner->fresh());
        $request->refresh();
        $this->assertSame([PartnerStatusEnum::PUBLISHED, $team->getKey()], [$request->status, $request->answered_by]);
        $this->assertNotNull($request->answered_at);

        // Chez l'auteur, administrateur : une seule carte, son suivi sans doublon.
        $cards = $this->api($author, 'GET', '/notifications')->assertOk()->json('data.items');
        $this->assertCount(1, $cards);
        $this->assertSame('Partenaire accepté', $cards[0]['title']);
        $history = $this->api($author, 'GET', '/notifications/'.$cards[0]['id'])->assertOk()->json('data.history');
        $this->assertSame(['Partenaire ajouté', 'Partenaire modifié', 'Partenaire accepté'], array_column($history, 'label'));

        // L'autre compte, rattaché après l'ajout et non administrateur : la modification, pas la réponse de l'équipe.
        $cards = $this->api($helper, 'GET', '/notifications')->assertOk()->json('data.items');
        $this->assertCount(1, $cards);
        $history = $this->api($helper, 'GET', '/notifications/'.$cards[0]['id'])->assertOk()->json('data.history');
        $this->assertSame(['Partenaire modifié'], array_column($history, 'label'));

        // Une modification après la réponse ouvre une nouvelle demande, un nouveau fil.
        $this->travel(1)->minutes();
        $this->api($author, 'PATCH', "/partners/{$partner->getKey()}", ['name' => 'Déménageurs du Wouri', 'description' => 'Encore.'])->assertNoContent();
        $this->assertSame([PartnerRequest::CREATED, PartnerRequest::UPDATED], PartnerRequest::query()->orderBy('id')->pluck('action')->all());
        $this->assertCount(2, $this->api($author, 'GET', '/notifications')->json('data.items'));
    }

    public function test_back_office_edits_notify_the_admins_but_not_the_back_office_user(): void
    {
        Notification::fake();
        $team = $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        $admin = $this->member();
        $helper = $this->member();
        $partner = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Meubles Akwa'], 'status' => PartnerStatusEnum::PUBLISHED]);
        // L'utilisateur du back-office, lui-même administrateur du partenaire, n'est pas prévenu.
        $partner->users()->attach([$admin->getKey() => ['is_admin' => true], $helper->getKey() => ['is_admin' => false], $team->getKey() => ['is_admin' => true]]);

        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['name' => ['fr' => 'Meubles Akwa Plus']])
            ->call('save')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($admin, PartnerPublished::class, function (PartnerPublished $notification) use ($admin): bool {
            $data = $notification->toArray($admin);

            return $data['title'] === 'Partenaire modifié'
                && $data['body'] === "Votre partenaire « Meubles Akwa Plus » a été modifié par l'équipe."
                && $notification->notificationThread($admin) === null
                && $notification->notificationUserId($admin) === null;
        });
        Notification::assertNotSentTo([$helper, $team], PartnerPublished::class);

        // Un changement de statut à l'enregistrement : la seule notification de statut, pas « modifié » en plus.
        Livewire::test(EditPartner::class, ['record' => $partner->getKey()])
            ->fillForm(['status' => PartnerStatusEnum::ARCHIVED->value])
            ->call('save')
            ->assertHasNoFormErrors();
        Notification::assertSentToTimes($admin, PartnerPublished::class, 1);
        Notification::assertSentTo($admin, PartnerStatusEnum::ARCHIVED->notification(), fn ($notification): bool => $notification->toArray($admin)['title'] === 'Partenaire archivé');
        Notification::assertNotSentTo([$helper, $team], PartnerStatusEnum::ARCHIVED->notification());
    }
}
