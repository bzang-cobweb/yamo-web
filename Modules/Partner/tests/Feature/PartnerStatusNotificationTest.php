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
use Modules\Partner\Listeners\NotifyPartnerUsersOfStatus;
use Modules\Partner\Models\Partner;
use Modules\Partner\Notifications\PartnerStatusNotification;
use Modules\Partner\Services\PartnerNotifierService;
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

    public function test_a_status_change_by_the_team_notifies_every_attached_account(): void
    {
        Notification::fake();
        [$partner, $admin, $helper] = $this->partner(PartnerStatusEnum::PENDING);
        $stranger = $this->member();

        $partner->update(['status' => PartnerStatusEnum::PUBLISHED]);

        foreach ([$admin, $helper] as $user) {
            Notification::assertSentTo($user, PartnerStatusNotification::class, function (PartnerStatusNotification $notification) use ($user, $partner): bool {
                $data = $notification->toArray($user);

                return $data['title'] === 'Partenaire publié'
                    && $data['body'] === 'Votre partenaire « Déménageurs du Wouri » est publié.'
                    && $data['link'] === '/profile/lists/partner.mine'
                    && $data['link_label'] === 'Mes partenaires'
                    && $data['partner_id'] === $partner->getKey()
                    && in_array('database', $notification->via($user), true);
            });
        }
        Notification::assertNotSentTo($stranger, PartnerStatusNotification::class);
    }

    public function test_a_member_editing_notifies_the_others_but_not_himself(): void
    {
        Notification::fake();
        [$partner, $admin, $helper] = $this->partner(PartnerStatusEnum::PUBLISHED);

        $this->withToken($helper->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr')
            ->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Wouri Déménagement'])->assertNoContent();

        Notification::assertSentTo($admin, PartnerStatusNotification::class, fn (PartnerStatusNotification $notification): bool => $notification->toArray($admin)['title'] === 'Partenaire en validation');
        Notification::assertNotSentTo($helper, PartnerStatusNotification::class);
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

        $partner = new Partner;
        $this->mock(PartnerNotifierService::class, fn (MockInterface $notifier) => $notifier->shouldReceive('statusChanged')->once()->with($partner, 7));

        app(NotifyPartnerUsersOfStatus::class)->handle(new PartnerStatusChanged($partner, PartnerStatusEnum::PENDING, 7));
    }
}
