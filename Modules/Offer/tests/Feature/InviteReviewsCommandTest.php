<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingReviewInvitation;
use Tests\TestCase;

/**
 * L'invitation à donner son avis, le lendemain de la fin d'un séjour ou d'une visite (décision 2026-11-22-invitation-avis).
 */
class InviteReviewsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    private function booking(int $endDaysAgo, BookingStatusEnum $status = BookingStatusEnum::ACCEPTED, string $type = 'stay'): Booking
    {
        $offer = Offer::factory()->create(['status' => 'published']);

        return Booking::factory()->for($offer)->for(User::factory()->create(['locale' => 'fr']))->status($status)->create([
            'type' => $type,
            'check_in' => today()->subDays($endDaysAgo + 2),
            'check_out' => $type === 'visit' ? null : today()->subDays($endDaysAgo),
            'guests' => $type === 'visit' ? null : 2,
        ]);
    }

    public function test_members_of_recently_completed_bookings_are_invited_once(): void
    {
        Notification::fake();
        $yesterday = $this->booking(1);
        $visit = $this->booking(1, type: 'visit');
        $old = $this->booking(10);
        $ongoing = $this->booking(-1);
        $pending = $this->booking(1, BookingStatusEnum::PENDING);
        $reviewed = $this->booking(1);
        $reviewed->offer->reviews()->create(['user_id' => $reviewed->user_id, 'rating' => 4, 'comment' => 'Bien.']);

        $this->artisan('booking:invite-reviews')->assertSuccessful();

        Notification::assertSentTo($yesterday->user, BookingReviewInvitation::class, fn (BookingReviewInvitation $invitation): bool => $invitation->booking->is($yesterday));
        Notification::assertSentTo($visit->user, BookingReviewInvitation::class);
        foreach ([$old, $ongoing, $pending, $reviewed] as $booking) {
            Notification::assertNotSentTo($booking->user, BookingReviewInvitation::class);
        }
        $this->assertNotNull($yesterday->refresh()->review_invited_at);

        // Une seule fois.
        $this->artisan('booking:invite-reviews')->assertSuccessful();
        Notification::assertSentToTimes($yesterday->user, BookingReviewInvitation::class, 1);
    }

    public function test_the_invitation_opens_the_review_form_of_the_home(): void
    {
        $booking = $this->booking(1);
        $visit = $this->booking(1, type: 'visit');
        app()->setLocale('fr');

        $data = (new BookingReviewInvitation($booking))->toArray($booking->user);
        $title = (string) $booking->offer->getTranslation('title', 'fr');
        $this->assertSame('Votre avis', $data['title']);
        $this->assertStringStartsWith('Votre séjour à « '.$title.' » (', $data['body']);
        $this->assertStringEndsWith(') est terminé. Laissez un avis.', $data['body']);
        $this->assertSame(['/offers/'.$booking->offer_id.'/review', 'Laisser un avis'], [$data['link'], $data['link_label']]);
        $this->assertSame([['text' => $title, 'link' => '/offers/'.$booking->offer_id]], $data['text_links']);
        $this->assertArrayNotHasKey('booking_id', $data);
        $this->assertStringStartsWith('Votre visite de « ', (new BookingReviewInvitation($visit))->toArray($visit->user)['body']);
        $this->assertStringContainsString('Laissez un avis dans l\'app.', (new BookingReviewInvitation($booking))->toSms($booking->user));
    }

    public function test_it_runs_every_morning(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('booking:invite-reviews')->assertSuccessful();
    }
}
