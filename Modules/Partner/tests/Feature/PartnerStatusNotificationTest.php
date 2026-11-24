<?php

namespace Modules\Partner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Events\PartnerStatusChanged;
use Modules\Partner\Events\PartnerSubmitted;
use Modules\Partner\Listeners\NotifyPartnerUsersOfStatus;
use Modules\Partner\Listeners\NotifyPartnerUsersOfSubmission;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerRequest;
use Modules\Partner\Notifications\PartnerNotification;
use Modules\Partner\Notifications\PartnerPending;
use Modules\Partner\Notifications\PartnerPublished;
use Modules\Partner\Services\PartnerNotifierService;
use Modules\Partner\Services\PartnerRequestService;
use Tests\TestCase;

/**
 * Un changement de statut prévient les comptes du partenaire, administrateurs ou non, sauf celui qui le fait
 * (décision 2026-11-24-partenaires-notifications).
 */
class PartnerStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

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
     * @return array{Partner, User, User}
     */
    private function partner(PartnerStatusEnum $status): array
    {
        $admin = $this->member();
        $helper = $this->member();
        $partner = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Déménageurs du Wouri'], 'status' => $status]);
        $partner->users()->attach($admin->getKey(), ['is_admin' => true]);
        $partner->users()->attach($helper->getKey(), ['is_admin' => false]);

        return [$partner, $admin, $helper];
    }

    public function test_a_status_change_by_the_team_notifies_the_admins_only(): void
    {
        Notification::fake();
        [$partner, $admin, $helper] = $this->partner(PartnerStatusEnum::PENDING);
        $stranger = $this->member();

        $partner->update(['status' => PartnerStatusEnum::PUBLISHED]);

        // Ses administrateurs seulement (décision 2026-11-24-partenaire-demandes).
        Notification::assertNotSentTo($helper, PartnerPublished::class);
        foreach ([$admin] as $user) {
            Notification::assertSentTo($user, PartnerPublished::class, function (PartnerPublished $notification) use ($user, $partner): bool {
                $data = $notification->toArray($user);

                // « Accepté » (décision 2026-11-24-partenaires-validation-notifications).
                return $data['title'] === 'Partenaire accepté'
                    && $data['body'] === "Votre partenaire « Déménageurs du Wouri » a été accepté par l'équipe."
                    && $data['link'] === '/profile/lists/partner.mine'
                    && $data['link_label'] === 'Mes partenaires'
                    && $data['partner_id'] === $partner->getKey()
                    && in_array('database', $notification->via($user), true);
            });
        }
        Notification::assertNotSentTo($stranger, PartnerPublished::class);
    }

    public function test_a_member_editing_notifies_the_others_and_himself_each_with_his_text(): void
    {
        // Décision 2026-11-24-partenaires-validation-notifications.
        Notification::fake();
        [$partner, $admin, $helper] = $this->partner(PartnerStatusEnum::PUBLISHED);

        $this->withToken($helper->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr')
            ->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Wouri Express', 'description' => 'Texte.'])->assertNoContent();

        $this->assertSame(PartnerStatusEnum::PENDING, $partner->fresh()->status);
        Notification::assertSentTo($admin, PartnerPending::class, function (PartnerPending $notification) use ($admin, $helper): bool {
            $data = $notification->toArray($admin);

            return $data['title'] === 'Partenaire modifié'
                && $data['body'] === '« Wouri Express » a été modifié par '.$helper->full_name.' ; il est en validation.'
                && $data['author'] === false
                && $data['partner_status'] === 'pending';
        });
        Notification::assertSentTo($helper, PartnerPending::class, function (PartnerPending $notification) use ($helper): bool {
            $data = $notification->toArray($helper);

            return $data['author'] === true
                && $data['body'] === "Votre partenaire « Wouri Express » est en validation : l'équipe vérifie vos modifications avant de l'afficher.";
        });
        // Pas de « statut changé » en plus pour le passage en validation.
        Notification::assertSentToTimes($admin, PartnerPending::class, 1);
        Notification::assertNotSentTo([$admin, $helper], PartnerPublished::class);
    }

    public function test_adding_a_partner_notifies_its_author(): void
    {
        Notification::fake();
        $member = $this->member();

        $this->withToken($member->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])
            ->postJson('/api/v1/partners', ['name' => 'Wouri Express', 'description' => 'Texte.'])->assertNoContent();

        Notification::assertSentTo($member, PartnerPending::class, function (PartnerPending $notification) use ($member): bool {
            $data = $notification->toArray($member);

            return $data['title'] === 'Partenaire ajouté'
                && $data['author'] === true
                && $data['body'] === "Votre partenaire « Wouri Express » est en validation : l'équipe le vérifie avant de l'afficher.";
        });
        Notification::assertSentToTimes($member, PartnerPending::class, 1);
    }

    public function test_the_submission_listener_hears_the_event_and_calls_the_notifier(): void
    {
        Event::fake();
        Event::assertListening(PartnerSubmitted::class, NotifyPartnerUsersOfSubmission::class);

        $partner = new Partner;
        $this->mock(PartnerNotifierService::class, fn (MockInterface $notifier) => $notifier->shouldReceive('submitted')->once()->with($partner, 7, true, null));

        app(NotifyPartnerUsersOfSubmission::class)->handle(new PartnerSubmitted($partner, 7, true));
    }

    public function test_nothing_is_sent_when_the_status_does_not_change(): void
    {
        Notification::fake();
        [$partner] = $this->partner(PartnerStatusEnum::PUBLISHED);

        $partner->update(['position' => 9, 'status' => PartnerStatusEnum::PUBLISHED]);

        Notification::assertNothingSent();
    }

    public function test_the_listener_hears_the_event_and_calls_the_notifier(): void
    {
        Event::fake();
        Event::assertListening(PartnerStatusChanged::class, NotifyPartnerUsersOfStatus::class);

        $partner = new Partner(['status' => PartnerStatusEnum::PUBLISHED]);
        $request = new PartnerRequest;
        $this->mock(PartnerRequestService::class, fn (MockInterface $requests) => $requests->shouldReceive('answer')->once()->with($partner, PartnerStatusEnum::PUBLISHED, 7)->andReturn($request));
        $this->mock(PartnerNotifierService::class, fn (MockInterface $notifier) => $notifier->shouldReceive('statusChanged')->once()->with($partner, 7, $request));

        app(NotifyPartnerUsersOfStatus::class)->handle(new PartnerStatusChanged($partner, PartnerStatusEnum::PENDING, 7));
    }

    public function test_each_status_has_its_notification_class(): void
    {
        // Décision 2026-11-24-partenaire-notification-par-statut.
        Notification::fake();
        [$partner, $admin] = $this->partner(PartnerStatusEnum::PUBLISHED);

        foreach ([PartnerStatusEnum::REJECTED, PartnerStatusEnum::ARCHIVED, PartnerStatusEnum::PUBLISHED] as $status) {
            $partner->update(['status' => $status]);
            Notification::assertSentTo($admin, $status->notification(), fn (PartnerNotification $notification): bool => $notification->toArray($admin)['partner_status'] === $status->value);
        }
        $this->assertSame(PartnerStatusEnum::REJECTED, PartnerStatusEnum::fromNotification(PartnerStatusEnum::REJECTED->notification()));
        $this->assertNull(PartnerStatusEnum::fromNotification('Autre'));
    }
}
