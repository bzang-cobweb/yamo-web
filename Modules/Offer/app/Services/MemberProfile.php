<?php

namespace Modules\Offer\Services;

use App\Support\MediaUrl;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;

/**
 * La page publique d'un membre, vue par un autre compte (décision
 * 2026-11-19-page-membre) :
 * - sa photo, son nom, « Membre depuis » ;
 * - sa présentation ;
 * - sa ville et son nombre de séjours acceptés.
 *
 * Jamais son e-mail, son téléphone ni son adresse précise.
 */
class MemberProfile
{
    /**
     * @return array{title: string, name: string, initials: string, photo: string|null, since: string, description: string|null, facts: list<array{label: string, value: string}>}
     */
    public function show(User $member, string $locale): array
    {
        $facts = [];
        $city = $this->city($member);
        if ($city !== null) {
            $facts[] = ['label' => __('offer::member.city', [], $locale), 'value' => $city];
        }
        $facts[] = ['label' => __('offer::member.stays', [], $locale), 'value' => (string) $this->stays($member)];
        $description = trim((string) $member->description);

        return [
            'title' => __('offer::member.title', [], $locale),
            'name' => (string) $member->full_name,
            'initials' => (string) $member->initials,
            'photo' => MediaUrl::onRequestHost($member->avatarUrl('member')),
            'since' => __('offer::member.since', ['date' => $member->created_at?->copy()->locale($locale)->isoFormat('MMMM YYYY') ?? ''], $locale),
            'description' => $description === '' ? null : $description,
            'facts' => $facts,
        ];
    }

    /** La ville de sa première adresse, ou `null`. */
    private function city(User $member): ?string
    {
        $city = trim((string) $member->addresses()->with('address')->first()?->address?->city);

        return $city === '' ? null : $city;
    }

    /** Ses séjours acceptés ou terminés, comme voyageur. */
    private function stays(User $member): int
    {
        return Booking::query()
            ->where('user_id', $member->getKey())
            ->where('type', BookingTypeEnum::STAY)
            ->whereIn('status', BookingStatusEnum::held())
            ->count();
    }
}
