<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * « Mes numéros » : le numéro principal (`users.phone`) puis les numéros supplémentaires, masqués ; ajouter un numéro
 * par un code SMS envoyé à ce numéro ; le retirer ; en faire le principal ; refus d'un numéro déjà au compte ou à un
 * autre (décision 2026-11-27-plusieurs-numeros).
 */
class UserPhonesApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;

    private const PRIMARY = '+41791112233';

    private const EXTRA = '+41782223344';

    private const OTHER = '+41763334455';

    private Country $switzerland;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        config(['auth.phone.inactive_days' => 180]);
        $this->switzerland = Country::query()->where('code', 'CH')->first()
            ?? Country::factory()->create(['code' => 'CH', 'dial_code' => '+41', 'name' => ['fr' => 'Suisse', 'en' => 'Switzerland'], 'native_name' => 'Schweiz', 'currency' => 'CHF']);
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create([
            'locale' => 'fr', 'phone' => self::PRIMARY, 'phone_country_id' => $this->switzerland->getKey(),
            'phone_verified_at' => now(), 'last_seen_at' => now(), ...$attributes,
        ]);
    }

    private function extra(User $user, string $phone, array $attributes = []): UserPhone
    {
        return $user->phones()->create(['phone' => $phone, 'phone_country_id' => $this->switzerland->getKey(), 'verified_at' => now(), ...$attributes]);
    }

    /**
     * @return array<string, mixed>
     */
    private function list(User $user): array
    {
        return $this->asUser($user)->getJson('/api/v1/lists/auth.phones')->assertOk()->json('data');
    }

    public function test_the_list_shows_the_primary_number_first_then_the_extra_numbers_masked(): void
    {
        $user = $this->member();
        $extra = $this->extra($user, self::EXTRA);

        $list = $this->list($user);

        $this->assertSame('Mes numéros', $list['title']);
        $this->assertSame(['label' => 'Ajouter un numéro', 'form' => 'auth.profile.phones.create', 'link' => null], $list['footer']);
        $this->assertCount(2, $list['items']);

        [$primary, $second] = $list['items'];
        $this->assertSame('primary', $primary['id']);
        $this->assertSame('+41 ••• 33', $primary['title']);
        $this->assertSame($this->switzerland->label(), $primary['subtitle']);
        $this->assertSame('Principal', $primary['tag']['label']);
        $this->assertSame([['form' => 'auth.profile.phone', 'record' => 'primary']], array_column($primary['actions'], 'form'));
        $this->assertSame('Changer', $primary['actions'][0]['label']);
        $this->assertNull($primary['delete']);

        $this->assertSame((string) $extra->getKey(), $second['id']);
        $this->assertSame('+41 ••• 44', $second['title']);
        $this->assertNull($second['tag'] ?? null);
        $this->assertSame('Rendre principal', $second['actions'][0]['label']);
        $this->assertSame('Retirer', $second['delete']['label']);
        $this->assertSame("/v1/auth/me/phones/{$extra->getKey()}", $second['delete']['endpoint']);
        $this->assertStringNotContainsString(self::EXTRA, json_encode($list, JSON_UNESCAPED_UNICODE));
    }

    public function test_an_unverified_or_missing_primary_number_offers_to_verify_or_add_it(): void
    {
        $unverified = $this->list($this->member(['phone_verified_at' => null]))['items'][0];
        $this->assertSame($this->switzerland->label().' · non vérifié', $unverified['subtitle']);
        $this->assertSame(['Changer', 'Vérifier'], array_column($unverified['actions'], 'label'));
        $this->assertSame('auth.profile.phone.verify', $unverified['actions'][1]['form']['form']);

        $none = $this->list(User::factory()->create(['locale' => 'fr', 'phone' => null]))['items'];
        $this->assertCount(1, $none);
        $this->assertSame('Aucun numéro principal', $none[0]['title']);
        $this->assertSame(['Ajouter'], array_column($none[0]['actions'], 'label'));
    }

    public function test_a_number_is_added_once_the_code_sent_to_it_by_sms_is_validated(): void
    {
        $user = $this->member();

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.phones.create')->assertOk()
            ->assertJsonPath('data.fields.0.type', 'phone')
            ->assertJsonPath('data.submit.success.action', 'back');

        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '078 222 33 44', 'phone_country' => 'CH'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'sms')
            ->assertJsonPath('destination', '+41 ••• 44')
            ->json('challenge');

        // Rien n'est ajouté avant le code.
        $this->assertSame(0, $user->phones()->count());

        $this->asUser($user)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::EXTRA)])
            ->assertOk()->assertJsonPath('data.id', $user->getKey());

        $phone = $user->phones()->sole();
        $this->assertSame(self::EXTRA, $phone->phone);
        $this->assertSame($this->switzerland->getKey(), $phone->phone_country_id);
        $this->assertNotNull($phone->verified_at);
        // Le principal ne change pas.
        $this->assertSame(self::PRIMARY, $user->fresh()->phone);
        $this->assertSame(['+41 ••• 33', '+41 ••• 44'], array_column($this->list($user)['items'], 'title'));
    }

    public function test_the_primary_number_a_number_already_listed_or_held_by_another_account_is_refused(): void
    {
        $user = $this->member();
        $this->extra($user, self::EXTRA);

        $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '079 111 22 33', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonPath('errors.phone.0', "C'est déjà votre numéro principal.");
        $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '078 222 33 44', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonPath('errors.phone.0', 'Ce numéro est déjà dans vos numéros.');

        // Le principal d'un autre compte, puis un numéro supplémentaire d'un autre compte.
        $other = $this->member(['phone' => self::OTHER]);
        $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '076 333 44 55', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->extra($other, '+41774445566');
        $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '077 444 55 66', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone');

        // Un numéro invalide.
        $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '123', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->assertSame(1, $user->phones()->count());
        $this->assertSame([], $this->sms->messages);
    }

    public function test_a_number_taken_between_the_code_and_its_validation_is_refused(): void
    {
        $user = $this->member();
        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '078 222 33 44', 'phone_country' => 'CH'])
            ->assertAccepted()->json('challenge');

        $this->member(['phone' => self::EXTRA]);

        $this->asUser($user)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::EXTRA)])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(0, $user->phones()->count());
    }

    public function test_a_number_of_an_inactive_account_is_released_to_the_new_holder(): void
    {
        $user = $this->member();
        $old = $this->member(['phone' => null, 'last_seen_at' => now()->subDays(365)]);
        $this->extra($old, self::EXTRA);

        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '078 222 33 44', 'phone_country' => 'CH'])
            ->assertAccepted()->json('challenge');
        $this->asUser($user)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::EXTRA)])->assertOk();

        $this->assertSame(1, $user->phones()->count());
        $this->assertSame(0, $old->phones()->count());
    }

    public function test_only_the_account_itself_validates_its_code(): void
    {
        $user = $this->member();
        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/phones', ['phone' => '078 222 33 44', 'phone_country' => 'CH'])->json('challenge');

        $this->asUser($this->member(['phone' => self::OTHER]))
            ->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::EXTRA)])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(0, UserPhone::query()->count());
    }

    public function test_a_number_is_removed(): void
    {
        $user = $this->member();
        $extra = $this->extra($user, self::EXTRA);

        $this->asUser($user)->deleteJson("/api/v1/auth/me/phones/{$extra->getKey()}")->assertNoContent();

        $this->assertModelMissing($extra);
        $this->assertSame(['+41 ••• 33'], array_column($this->list($user)['items'], 'title'));
    }

    public function test_an_extra_number_becomes_the_primary_one_and_the_previous_one_joins_the_list(): void
    {
        $user = $this->member();
        $extra = $this->extra($user, self::EXTRA);

        $this->asUser($user)->postJson("/api/v1/auth/me/phones/{$extra->getKey()}/primary")->assertOk()
            ->assertJsonPath('data.phone', self::EXTRA);

        $user->refresh();
        $this->assertSame(self::EXTRA, $user->phone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame([self::PRIMARY], $user->phones()->pluck('phone')->all());
        $this->assertSame(['+41 ••• 44', '+41 ••• 33'], array_column($this->list($user)['items'], 'title'));
    }

    public function test_an_extra_number_held_since_as_primary_elsewhere_cannot_become_primary(): void
    {
        $user = $this->member();
        $extra = $this->extra($user, self::EXTRA);
        $this->member(['phone' => self::EXTRA, 'last_seen_at' => now()->subDays(365)]);

        $this->asUser($user)->postJson("/api/v1/auth/me/phones/{$extra->getKey()}/primary")
            ->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertSame(self::PRIMARY, $user->fresh()->phone);
    }

    public function test_changing_the_primary_number_to_an_extra_one_removes_it_from_the_list(): void
    {
        $user = $this->member();
        $this->extra($user, self::EXTRA);

        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/phone', ['phone' => '078 222 33 44', 'phone_country' => 'CH'])
            ->assertAccepted()->json('challenge');
        $this->asUser($user)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::EXTRA)])->assertOk();

        $this->assertSame(self::EXTRA, $user->fresh()->phone);
        $this->assertSame(0, $user->phones()->count());
    }

    public function test_the_number_of_another_account_is_not_found(): void
    {
        $other = $this->member(['phone' => self::OTHER]);
        $extra = $this->extra($other, self::EXTRA);
        $user = $this->member();

        $this->asUser($user)->deleteJson("/api/v1/auth/me/phones/{$extra->getKey()}")->assertNotFound();
        $this->asUser($user)->postJson("/api/v1/auth/me/phones/{$extra->getKey()}/primary")->assertNotFound();
        $this->assertModelExists($extra);
        $this->assertSame(['+41 ••• 33'], array_column($this->list($user)['items'], 'title'));
    }

    public function test_guests_are_refused(): void
    {
        $extra = $this->extra($this->member(), self::EXTRA);

        $this->getJson('/api/v1/lists/auth.phones')->assertUnauthorized();
        $this->postJson('/api/v1/auth/me/phones', ['phone' => '078 222 33 44', 'phone_country' => 'CH'])->assertUnauthorized();
        $this->deleteJson("/api/v1/auth/me/phones/{$extra->getKey()}")->assertUnauthorized();
        $this->postJson("/api/v1/auth/me/phones/{$extra->getKey()}/primary")->assertUnauthorized();
        $this->getJson('/api/v1/forms/auth.profile.phones.create')->assertUnauthorized();
    }
}
