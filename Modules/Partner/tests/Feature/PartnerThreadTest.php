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
use Modules\Partner\Notifications\PartnerPublished;
use Modules\Partner\Services\PartnerService;

/**
 * Le fil des notifications d'un partenaire, un par destinataire (décision 2026-11-24-partenaire-fil) ; au back-office,
 * seuls ses administrateurs sont prévenus, jamais celui qui agit (décision 2026-11-24-partenaire-demandes).
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

    public function test_a_partner_gathers_its_notifications_in_one_thread_per_recipient(): void
    {
        $author = $this->member();
        $this->api($author, 'POST', '/partners', ['name' => 'Déménageurs du Wouri', 'description' => 'Texte.'])->assertNoContent();
        $partner = Partner::query()->where('name->fr', 'Déménageurs du Wouri')->sole();
        $helper = $this->member();
        $partner->users()->attach($helper->getKey(), ['is_admin' => false]);

        // Ajouté, modifié, accepté, puis modifié de nouveau. Une minute entre les étapes : elles se suivent dans l'ordre.
        $this->travel(1)->minutes();
        $this->api($helper, 'PATCH', "/partners/{$partner->getKey()}", ['name' => 'Déménageurs du Wouri', 'description' => 'Autre texte.'])->assertNoContent();
        // L'équipe valide au back-office (l'action « Valider » appelle `accept()`, testée dans PartnerResourceTest).
        $this->travel(1)->minutes();
        Filament::setServingStatus();
        $this->actingAsUserWith(PermissionEnum::VIEW_PARTNERS, PermissionEnum::UPDATE_PARTNERS);
        app(PartnerService::class)->accept($partner->fresh());
        $this->travel(1)->minutes();
        $this->api($author, 'PATCH', "/partners/{$partner->getKey()}", ['name' => 'Déménageurs du Wouri', 'description' => 'Encore.'])->assertNoContent();

        // Chez l'auteur, administrateur : une seule carte pour le partenaire, son suivi sans doublon.
        $cards = $this->api($author, 'GET', '/notifications')->assertOk()->json('data.items');
        $this->assertCount(1, $cards);
        $this->assertSame('Partenaire modifié', $cards[0]['title']);
        $history = $this->api($author, 'GET', '/notifications/'.$cards[0]['id'])->assertOk()->json('data.history');
        $this->assertSame(['Partenaire ajouté', 'Partenaire modifié', 'Partenaire accepté', 'Partenaire modifié'], array_column($history, 'label'));
        $this->assertSame($partner->thread($author->getKey()), $author->notifications()->value('thread'));

        // L'autre compte, rattaché après l'ajout et non administrateur : les deux modifications, pas la réponse de l'équipe.
        $cards = $this->api($helper, 'GET', '/notifications')->assertOk()->json('data.items');
        $this->assertCount(1, $cards);
        $history = $this->api($helper, 'GET', '/notifications/'.$cards[0]['id'])->assertOk()->json('data.history');
        $this->assertSame(['Partenaire modifié', 'Partenaire modifié'], array_column($history, 'label'));
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

        Notification::assertSentTo($admin, PartnerPublished::class, function (PartnerPublished $notification) use ($admin, $partner): bool {
            $data = $notification->toArray($admin);

            return $data['title'] === 'Partenaire modifié'
                && $data['body'] === "Votre partenaire « Meubles Akwa Plus » a été modifié par l'équipe."
                && $notification->notificationThread($admin) === $partner->thread($admin->getKey())
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
