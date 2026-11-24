<?php

namespace Modules\Partner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerNotifierService;
use Tests\TestCase;

/**
 * Les notifications des partenaires dans « Notifications » : le type « Partenaire », le filtre
 * « Statut » commun, la pastille,
 * la recherche par nom (décisions 2026-11-24-partenaires-notifications-filtre et
 * 2026-11-24-notifications-types-reservation-partenaire).
 */
class PartnerNotificationFilterTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(User $user, string $query = ''): array
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications'.$query)->assertOk()->json('data.items');
    }

    public function test_partner_notifications_are_found_by_type_status_and_name(): void
    {
        $cameroon = Country::factory()->create(['code' => 'CM']);
        $member = $this->member();
        $other = $this->member();
        $wouri = Partner::factory()->for($cameroon)->create(['name' => ['fr' => 'Déménageurs du Wouri'], 'status' => PartnerStatusEnum::PENDING]);
        $akwa = Partner::factory()->for($cameroon)->create(['name' => ['fr' => 'Meubles Akwa'], 'status' => PartnerStatusEnum::PENDING]);
        foreach ([$wouri, $akwa] as $partner) {
            $partner->users()->attach([$member->getKey() => ['is_admin' => true], $other->getKey() => ['is_admin' => false]]);
        }
        $notifier = app(PartnerNotifierService::class);
        // Wouri ajouté par l'autre compte, puis accepté ; Akwa modifié par le membre.
        $notifier->submitted($wouri, $other->getKey(), true);
        $wouri->update(['status' => PartnerStatusEnum::PUBLISHED]);
        $notifier->submitted($akwa, $member->getKey(), false);
        $member->notifications()->create(['id' => (string) str()->uuid(), 'type' => 'Test', 'data' => ['title' => 'Autre', 'body' => 'Texte']]);

        // Le type « Partenaire » : les trois, sans l'autre notification.
        $this->assertEqualsCanonicalizing(['Partenaire ajouté', 'Partenaire accepté', 'Partenaire modifié'], array_column($this->items($member, '?filter[type]=partner'), 'title'));

        // Par statut, avec sa pastille.
        $pending = $this->items($member, '?filter[type]=partner&filter[status]=partner_pending');
        $this->assertEqualsCanonicalizing(['Partenaire ajouté', 'Partenaire modifié'], array_column($pending, 'title'));
        $this->assertSame(['En validation'], array_values(array_unique(array_column(array_column($pending, 'tag'), 'label'))));
        $published = $this->items($member, '?filter[type]=partner&filter[status]=partner_published');
        $this->assertSame(['Partenaire accepté'], array_column($published, 'title'));
        $this->assertSame('Publié', $published[0]['tag']['label']);

        // Le filtre « Statut » commun, avec le groupe.
        $this->assertSame(['Partenaire accepté'], array_column($this->items($member, '?filter[type]=partner&filter[status]=partner_published'), 'title'));
        $this->assertEqualsCanonicalizing(['Partenaire ajouté', 'Partenaire modifié'], array_column($this->items($member, '?filter[type]=partner&filter[status]=partner_pending'), 'title'));

        // Par le nom du partenaire.
        $this->assertEqualsCanonicalizing(['Partenaire modifié'], array_column($this->items($member, '?search=Akwa'), 'title'));

        // « Partenaire » seulement ; ses statuts dans « Statut ».
        $filters = collect($this->withToken($member->createToken('iPhone')->plainTextToken)->withHeaders(['X-Country' => 'CM', 'Accept-Language' => 'fr'])
            ->getJson('/api/v1/notifications')->json('data.filters'))->keyBy('name');
        $this->assertSame(['partner' => 'Partenaire'], array_column($filters['type']['options'], 'label', 'value'));
        $statuses = collect($filters['status']['options'])->where('parent', 'partner');
        $this->assertSame(['En validation', 'Publié', 'Refusé', 'Archivé'], $statuses->pluck('label')->all());
        $this->assertArrayNotHasKey('partner_status', $filters->all());
    }
}
