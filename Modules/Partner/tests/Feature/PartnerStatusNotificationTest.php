<?php

namespace Modules\Partner\Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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
use Modules\Partner\Notifications\PartnerNotification;
use Modules\Partner\Notifications\PartnerPending;
use Modules\Partner\Notifications\PartnerPublished;
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

    /** Le compte du back-office de la configuration, prévenu des demandes depuis l'app. */
    private function team(): User
    {
        $team = User::factory()->create(['locale' => 'fr']);
        Setting::current()->update(['partner_notify_user_id' => $team->getKey()]);

        return $team;
    }

    /** `$author` a fait une demande depuis l'app : sa notification est dans le fil du partenaire. */
    private function submittedBy(Partner $partner, User $author): void
    {
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(), 'type' => PartnerPending::class, 'notifiable_type' => $author->getMorphClass(), 'notifiable_id' => $this->member()->getKey(),
            'user_id' => $author->getKey(), 'thread' => $partner->groupThread(), 'data' => ['title' => 'Partenaire modifié', 'body' => 'Texte'],
        ]);
    }

    public function test_a_status_change_by_the_team_notifies_the_author_of_the_request(): void
    {
        // Décision 2026-11-24-notifications-fil-de-groupe : l'auteur de la dernière demande depuis l'app, lui seul.
        [$partner, $admin, $helper] = $this->partner(PartnerStatusEnum::PENDING);
        $this->submittedBy($partner, $helper);
        Notification::fake();

        $partner->update(['status' => PartnerStatusEnum::PUBLISHED]);

        Notification::assertSentTo($helper, PartnerPublished::class, function (PartnerPublished $notification) use ($helper, $partner): bool {
            $data = $notification->toArray($helper);

            // « Accepté » (décision 2026-11-24-partenaires-validation-notifications), dans le fil du partenaire.
            return $data['title'] === 'Partenaire accepté'
                && $data['body'] === "Votre partenaire « Déménageurs du Wouri » a été accepté par l'équipe."
                && $data['link'] === '/profile/lists/partner.mine'
                && $data['partner_id'] === $partner->getKey()
                && $notification->notificationThread($helper) === 'partner:group:'.$partner->getKey()
                && $notification->notificationUserId($helper) === null;
        });
        // Ses administrateurs : e-mail et SMS, sans notification dans l'onglet (décision 2026-11-24-partenaire-admins-email-sms).
        Notification::assertSentTo($admin, PartnerPublished::class, fn ($notification, array $channels): bool => ! in_array('database', $channels, true));
    }

    public function test_a_member_editing_notifies_the_back_office_account_only(): void
    {
        // Décision 2026-11-24-notifications-fil-de-groupe.
        $team = $this->team();
        Notification::fake();
        [$partner, $admin, $helper] = $this->partner(PartnerStatusEnum::PUBLISHED);

        $this->withToken($helper->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr')
            ->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Wouri Express', 'description' => 'Texte.'])->assertNoContent();

        // Le statut ne change pas : affiché, il reste en ligne (décision 2026-11-24-partenaire-modification-sans-validation).
        $this->assertSame(PartnerStatusEnum::PUBLISHED, $partner->fresh()->status);
        Notification::assertSentTo($team, PartnerPublished::class, function (PartnerPublished $notification) use ($team, $helper): bool {
            $data = $notification->toArray($team);

            return $data['title'] === 'Partenaire modifié'
                && $data['body'] === '« Wouri Express » a été modifié par '.$helper->full_name.'.'
                && $notification->notificationUserId($team) === $helper->getKey()
                && $data['partner_status'] === 'published';
        });
        // Ni ses comptes ni son auteur ; une seule notification, pas de « statut changé ».
        Notification::assertSentToTimes($team, PartnerPublished::class, 1);
        // Ses administrateurs : e-mail et SMS, sans notification dans l'onglet ; celui qui agit, rien (décision
        // 2026-11-24-partenaire-admins-email-sms).
        Notification::assertSentTo($admin, PartnerPublished::class, fn ($notification, array $channels): bool => ! in_array('database', $channels, true));
        Notification::assertNotSentTo($helper, PartnerPublished::class);
        Notification::assertNotSentTo([$admin, $helper, $team], PartnerPending::class);
    }

    public function test_adding_a_partner_notifies_the_back_office_account_not_its_author(): void
    {
        $team = $this->team();
        Notification::fake();
        $member = $this->member();

        $this->withToken($member->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])
            ->postJson('/api/v1/partners', ['name' => 'Wouri Express', 'description' => 'Texte.'])->assertNoContent();

        Notification::assertSentTo($team, PartnerPending::class, fn (PartnerPending $notification): bool => $notification->toArray($team)['title'] === 'Partenaire ajouté'
            && $notification->notificationUserId($team) === $member->getKey());
        // Celui qui agit, administrateur du partenaire qu'il propose, ne reçoit rien.
        Notification::assertNotSentTo($member, PartnerPending::class);

        // Sans compte configuré : personne.
        Setting::current()->update(['partner_notify_user_id' => null]);
        $this->withToken($member->createToken('iPhone')->plainTextToken)->withHeaders(['Accept-Language' => 'fr', 'X-Country' => 'CM'])
            ->postJson('/api/v1/partners', ['name' => 'Autre', 'description' => 'Texte.'])->assertNoContent();
        Notification::assertSentToTimes($team, PartnerPending::class, 1);
    }

    public function test_the_submission_listener_hears_the_event_and_calls_the_notifier(): void
    {
        Event::fake();
        Event::assertListening(PartnerSubmitted::class, NotifyPartnerUsersOfSubmission::class);

        $partner = new Partner;
        $this->mock(PartnerNotifierService::class, fn (MockInterface $notifier) => $notifier->shouldReceive('submitted')->once()->with($partner, 7, true));

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
        $this->mock(PartnerNotifierService::class, fn (MockInterface $notifier) => $notifier->shouldReceive('statusChanged')->once()->with($partner, 7));

        app(NotifyPartnerUsersOfStatus::class)->handle(new PartnerStatusChanged($partner, PartnerStatusEnum::PENDING, 7));
    }

    public function test_each_status_has_its_notification_class(): void
    {
        // Décision 2026-11-24-partenaire-notification-par-statut.
        [$partner, $admin] = $this->partner(PartnerStatusEnum::PUBLISHED);
        $this->submittedBy($partner, $admin);
        Notification::fake();

        foreach ([PartnerStatusEnum::REJECTED, PartnerStatusEnum::AWAITING_PAYMENT, PartnerStatusEnum::PUBLISHED] as $status) {
            $partner->update(['status' => $status]);
            Notification::assertSentTo($admin, $status->notification(), fn (PartnerNotification $notification): bool => $notification->toArray($admin)['partner_status'] === $status->value);
        }
        $this->assertSame(PartnerStatusEnum::REJECTED, PartnerStatusEnum::fromNotification(PartnerStatusEnum::REJECTED->notification()));
        $this->assertNull(PartnerStatusEnum::fromNotification('Autre'));
    }
}
