<?php

namespace Modules\Partner\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Form\Models\FormUpload;
use Modules\Form\Tests\Concerns\MakesFormUploads;
use Modules\Location\Models\Country;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Tests\TestCase;

/**
 * « Mes partenaires » : un membre propose, modifie et retire ses partenaires ; plusieurs comptes par partenaire,
 * `is_admin` pour celui qui le propose (décision 2026-11-24-partenaires-par-les-membres).
 */
class MyPartnerApiTest extends TestCase
{
    use MakesFormUploads, RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        // Le seul pays actif : l'espace pays courant.
        $this->cameroon = Country::factory()->create(['code' => 'CM']);
        Storage::fake(FormUpload::DISK);
        Storage::fake('public');
    }

    private function member(): User
    {
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findOrCreate(RoleEnum::MEMBER->value, 'web'));

        return $user;
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    public function test_the_menu_links_to_my_partners(): void
    {
        $menu = $this->asUser($this->member())->getJson('/api/v1/auth/me/menu')->assertOk()->json('data');
        $entry = collect($menu)->firstWhere('key', 'partners');

        $this->assertSame(['Mes partenaires', 'partner.mine', 'Partenaire'], [$entry['label'], $entry['list'], $entry['group']['label']]);
    }

    public function test_a_member_proposes_a_partner_waiting_for_review_and_administers_it(): void
    {
        $user = $this->member();
        $form = $this->asUser($user)->getJson('/api/v1/forms/partner.partner')->assertOk()->json('data');
        $this->assertSame(['name', 'logo', 'description', 'url', 'cta_label', 'contact_email', 'contact_phone'], array_column($form['fields'], 'name'));
        $this->assertSame('Proposer un partenaire', $form['title']);

        $this->asUser($user)->postJson('/api/v1/partners', [
            'name' => 'Déménageurs du Wouri',
            'logo' => [$this->pendingUpload($user, 'logo', 'partner.partner')],
            'description' => 'Déménagement clés en main.',
            'url' => 'https://wouri.test',
            'cta_label' => 'Demander un devis',
            'contact_email' => 'commercial@wouri.test',
        ])->assertNoContent();

        $partner = Partner::query()->sole();
        // Dans le pays courant, la langue du compte, pas publié ; le compte en est administrateur.
        $this->assertSame([$this->cameroon->getKey(), 'Déménageurs du Wouri', 'https://wouri.test', PartnerStatusEnum::PENDING], [$partner->country_id, $partner->getTranslation('name', 'fr'), $partner->getTranslation('url', 'fr'), $partner->status]);
        $this->assertNotNull($partner->logoUrl());
        $this->assertTrue($partner->hasAdmin($user->getKey()));
        // Pas visible dans l'app tant que l'équipe ne l'a pas publié.
        $this->withHeader('X-Country', 'CM')->getJson("/api/v1/partners/{$partner->getKey()}")->assertNotFound();

        // « Mes partenaires » : en attente de validation, modifiable et retirable.
        $item = $this->asUser($user)->getJson('/api/v1/lists/partner.mine')->assertOk()->json('data.items.0');
        $this->assertSame(['Déménageurs du Wouri', 'En attente de validation', 'warning'], [$item['title'], $item['tag']['label'], $item['tag']['tone']]);
        $this->assertSame('partner.partner', $item['edit']['form']);
        $this->assertNotNull($item['delete']);
        $this->assertSame('partner.partner', $this->asUser($user)->getJson('/api/v1/lists/partner.mine')->json('data.footer.form'));

        // Invalides : un nom manquant, un lien qui n'en est pas un.
        $this->asUser($user)->postJson('/api/v1/partners', ['url' => 'pas un lien'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'url']);
    }

    public function test_editing_prefills_saves_the_language_and_sends_it_back_for_review(): void
    {
        $user = $this->member();
        $partner = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Avant', 'en' => 'Before'], 'status' => PartnerStatusEnum::PUBLISHED]);
        $partner->users()->attach($user->getKey(), ['is_admin' => true]);

        $fields = collect($this->asUser($user)->getJson("/api/v1/forms/partner.partner?record={$partner->getKey()}")->assertOk()->json('data.fields'))->keyBy('name');
        $this->assertSame('Avant', $fields['name']['default']);

        $this->asUser($user)->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Après', 'logo' => []])->assertNoContent();
        $partner->refresh();
        // Seule la langue du compte change ; il repasse en attente de validation.
        $this->assertSame(['Après', 'Before', PartnerStatusEnum::PENDING], [$partner->getTranslation('name', 'fr'), $partner->getTranslation('name', 'en'), $partner->status]);
    }

    public function test_attached_accounts_edit_only_admins_remove_and_others_do_not_see_it(): void
    {
        $admin = $this->member();
        $helper = $this->member();
        $stranger = $this->member();
        $partner = Partner::factory()->for($this->cameroon)->create();
        $partner->users()->attach($admin->getKey(), ['is_admin' => true]);
        $partner->users()->attach($helper->getKey(), ['is_admin' => false]);

        // Un compte étranger : le partenaire n'existe pas pour lui.
        $this->asUser($stranger)->getJson("/api/v1/forms/partner.partner?record={$partner->getKey()}")->assertNotFound();
        $this->asUser($stranger)->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Pirate'])->assertNotFound();
        $this->asUser($stranger)->deleteJson("/api/v1/partners/{$partner->getKey()}")->assertNotFound();
        $this->assertSame([], $this->asUser($stranger)->getJson('/api/v1/lists/partner.mine')->json('data.items'));

        // Un compte rattaché sans être administrateur : il modifie, il ne retire pas.
        $this->asUser($helper)->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Modifié'])->assertNoContent();
        $this->asUser($helper)->deleteJson("/api/v1/partners/{$partner->getKey()}")->assertForbidden();
        $this->assertNull($this->asUser($helper)->getJson('/api/v1/lists/partner.mine')->json('data.items.0.delete'));

        // L'administrateur retire.
        $this->asUser($admin)->deleteJson("/api/v1/partners/{$partner->getKey()}")->assertNoContent();
        $this->assertNull(Partner::query()->find($partner->getKey()));
    }

    public function test_my_partners_show_their_status(): void
    {
        // Décision 2026-11-24-partenaires-statuts.
        $user = $this->member();
        $partners = [
            'Refusé' => ['status' => PartnerStatusEnum::REJECTED],
            'Archivé' => ['status' => PartnerStatusEnum::ARCHIVED],
            'Affiché' => ['status' => PartnerStatusEnum::PUBLISHED],
            'Pas affiché' => ['status' => PartnerStatusEnum::PUBLISHED, 'starts_at' => now()->addMonth()],
        ];
        foreach ($partners as $name => $attributes) {
            Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => $name], ...$attributes])->users()->attach($user->getKey(), ['is_admin' => true]);
        }

        $tags = collect($this->asUser($user)->getJson('/api/v1/lists/partner.mine')->assertOk()->json('data.items'))
            ->mapWithKeys(fn (array $item): array => [$item['title'] => [$item['tag']['label'], $item['tag']['tone']]])->all();

        $this->assertSame(['Refusé', 'danger'], $tags['Refusé']);
        $this->assertSame(['Archivé', 'neutral'], $tags['Archivé']);
        $this->assertSame(['Affiché', 'success'], $tags['Affiché']);
        $this->assertSame(['Pas affiché', 'neutral'], $tags['Pas affiché']);
    }
}
