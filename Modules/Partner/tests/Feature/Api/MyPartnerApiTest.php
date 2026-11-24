<?php

namespace Modules\Partner\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_a_logo_smaller_than_the_largest_conversion_is_refused(): void
    {
        // Au moins la conversion `list` (720 px) sur le plus grand côté (décisions 2026-11-24-logo-taille-minimale et
        // 2026-11-24-image-taille-minimale-enregistree).
        Storage::fake(FormUpload::DISK);
        $user = $this->member();
        $send = fn (int $width, int $height) => $this->asUser($user)->post('/api/v1/forms/partner.partner/uploads/logo', ['file' => UploadedFile::fake()->image('logo.png', $width, $height)], ['Accept' => 'application/json']);

        $send(719, 719)->assertUnprocessable()->assertJsonPath('errors.file.0', "L'image doit mesurer au moins 720 px sur son plus grand côté.");
        // Un bandeau ou un logo en hauteur : son grand côté suffit.
        $send(1600, 400)->assertCreated();
        $send(400, 720)->assertCreated();

        $logo = collect($this->asUser($user)->getJson('/api/v1/forms/partner.partner')->json('data.fields'))->firstWhere('name', 'logo');
        $this->assertSame('Logo carré, au moins 720 × 720 px.', $logo['help']);
    }

    public function test_a_member_proposes_a_partner_waiting_for_review_and_administers_it(): void
    {
        $user = $this->member();
        $form = $this->asUser($user)->getJson('/api/v1/forms/partner.partner')->assertOk()->json('data');
        $this->assertSame(['logo', 'name', 'slogan', 'description', 'contact_phone', 'contact_email', 'url', 'cta_label'], array_column($form['fields'], 'name'));
        // En étapes, sans « Langue » à la création (décision 2026-11-24-mes-partenaires-cartes).
        $this->assertSame([['Présentation', ['logo', 'name', 'slogan', 'description']], ['Contact', ['contact_phone', 'contact_email', 'url', 'cta_label']]], array_map(fn (array $step): array => [$step['title'], $step['fields']], $form['steps']));
        $this->assertSame('Ajouter un partenaire', $form['title']);
        $this->assertSame('center', $form['title_align']);
        // Un seul logo, son propre bouton (décision 2026-11-24-champ-fichier-libelle-ajout).
        $this->assertSame(['max_files' => 1, 'add_label' => 'Ajouter un logo'], array_intersect_key($form['fields'][0]['upload'], ['max_files' => 0, 'add_label' => 0]));

        $this->asUser($user)->postJson('/api/v1/partners', [
            'name' => 'Déménageurs du Wouri',
            'slogan' => 'On porte, vous respirez.',
            'logo' => [$this->pendingUpload($user, 'logo', 'partner.partner')],
            'description' => 'Déménagement clés en main.',
            'url' => 'https://wouri.test',
            'cta_label' => 'Demander un devis',
            'contact_email' => 'commercial@wouri.test',
            'contact_phone' => '6 70 00 00 05',
            'contact_phone_country' => 'CM',
        ])->assertNoContent();

        $partner = Partner::query()->sole();
        // Dans le pays courant, la langue du compte, pas publié ; le compte en est administrateur.
        $this->assertSame([$this->cameroon->getKey(), 'Déménageurs du Wouri', 'https://wouri.test', PartnerStatusEnum::PENDING], [$partner->country_id, $partner->getTranslation('name', 'fr'), $partner->getTranslation('url', 'fr'), $partner->status]);
        $this->assertNotNull($partner->logoUrl());
        // Le téléphone : l'indicatif du pays, seul proposé et verrouillé ; enregistré au format international, lu
        // lisible (décision 2026-11-24-partenaire-telephone-pays).
        $phone = collect($form['fields'])->firstWhere('name', 'contact_phone');
        $this->assertSame(['phone', true, ['CM']], [$phone['type'], $phone['locked'], array_column($phone['options'], 'value')]);
        $this->assertSame('CM', $phone['default']['country']);
        $this->assertSame('+237670000005', $partner->contact_phone);
        $this->asUser($user)->getJson("/api/v1/partners/{$partner->getKey()}")->assertJsonPath('data.contact_phone', '+237 6 70 00 00 05');
        // Un autre pays est refusé ; un numéro qui n'est pas camerounais aussi.
        $this->asUser($user)->postJson('/api/v1/partners', ['name' => 'X', 'description' => 'Y', 'contact_phone' => '6 70 00 00 05', 'contact_phone_country' => 'FR'])
            ->assertUnprocessable()->assertJsonValidationErrors(['contact_phone_country']);
        $this->asUser($user)->postJson('/api/v1/partners', ['name' => 'X', 'description' => 'Y', 'contact_phone' => '12', 'contact_phone_country' => 'CM'])
            ->assertUnprocessable()->assertJsonValidationErrors(['contact_phone']);
        // Le slogan, facultatif (décision 2026-11-24-partenaire-slogan).
        $this->assertSame('On porte, vous respirez.', $partner->getTranslation('slogan', 'fr'));
        $this->asUser($user)->getJson("/api/v1/partners/{$partner->getKey()}")->assertJsonPath('data.slogan', 'On porte, vous respirez.');
        $this->assertTrue($partner->hasAdmin($user->getKey()));
        // Pas visible dans l'app, sans compte, tant que l'équipe ne l'a pas publié.
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withHeader('X-Country', 'CM')->getJson("/api/v1/partners/{$partner->getKey()}")->assertNotFound();

        // « Mes partenaires » : « 1 partenaire » ; le statut sur le logo, la description sur une ligne ; la carte
        // ouvre sa page, « Modifier » et « Retirer » dans le menu (décision 2026-11-24-mes-partenaires-cartes).
        $list = $this->asUser($user)->getJson('/api/v1/lists/partner.mine')->assertOk()->json('data');
        $item = $list['items'][0];
        $this->assertSame('1 partenaire', $list['heading']['title']);
        $this->assertTrue($list['menu']);
        // Le statut au-dessus du nom, pas sur le logo (décision 2026-11-24-mes-partenaires-comme-partenaires).
        $this->assertSame(['Déménageurs du Wouri', 'En validation', 'warning', false], [$item['title'], $item['tag']['label'], $item['tag']['tone'], $item['tag']['on_image'] ?? false]);
        $this->assertSame([1, '/partners/'.$partner->getKey()], [$item['subtitle_lines'], $item['link']]);
        // Le logo en entier, à gauche, comme la page « Partenaires » (décision 2026-11-24-mes-partenaires-comme-partenaires).
        $this->assertSame(['contain', 'side'], [$item['image_fit'], $item['image_layout']]);
        // Sous le nom, le slogan ; sans slogan, la description (décision 2026-11-24-mes-partenaires-slogan).
        $this->assertSame('On porte, vous respirez.', $item['subtitle']);
        $partner->forgetTranslation('slogan', 'fr')->save();
        $this->assertSame('Déménagement clés en main.', $this->asUser($user)->getJson('/api/v1/lists/partner.mine')->json('data.items.0.subtitle'));
        $this->assertSame('partner.partner', $item['edit']['form']);
        $this->assertNotNull($item['delete']);
        // Sa page, pas encore publiée : pour ses comptes seulement.
        $this->asUser($user)->getJson("/api/v1/partners/{$partner->getKey()}")->assertOk()->assertJsonPath('data.name', 'Déménageurs du Wouri');
        $this->asUser($this->member())->getJson("/api/v1/partners/{$partner->getKey()}")->assertNotFound();
        // « Devenir partenaire » ouvre le formulaire d'ajout (décision 2026-11-24-devenir-partenaire).
        $this->assertSame(['label' => 'Devenir partenaire', 'form' => 'partner.partner', 'link' => null], $this->asUser($user)->getJson('/api/v1/lists/partner.mine')->json('data.footer'));

        // Invalides : un nom ou une description manquants, un lien qui n'en est pas un ; le logo est facultatif
        // (décision 2026-11-24-partenaire-description-obligatoire).
        $this->asUser($user)->postJson('/api/v1/partners', ['url' => 'pas un lien'])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'url'])->assertJsonMissingValidationErrors(['logo']);
        $this->asUser($user)->postJson('/api/v1/partners', ['name' => 'Sans logo', 'description' => 'Facultatif.'])->assertNoContent();
        // Le nom et le slogan : 50 caractères au plus (décision 2026-11-24-partenaire-nom-slogan-50).
        $this->asUser($user)->postJson('/api/v1/partners', ['name' => str_repeat('n', 51), 'slogan' => str_repeat('s', 51), 'description' => 'Texte.'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'slogan']);
        $this->asUser($user)->postJson('/api/v1/partners', ['name' => str_repeat('n', 50), 'slogan' => str_repeat('s', 50), 'description' => 'Texte.'])->assertNoContent();
    }

    public function test_editing_prefills_saves_the_language_and_sends_it_back_for_review(): void
    {
        $user = $this->member();
        $partner = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Avant', 'en' => 'Before'], 'status' => PartnerStatusEnum::PUBLISHED]);
        $partner->users()->attach($user->getKey(), ['is_admin' => true]);

        $form = $this->asUser($user)->getJson("/api/v1/forms/partner.partner?record={$partner->getKey()}")->assertOk()->json('data');
        $fields = collect($form['fields'])->keyBy('name');
        $this->assertSame('Avant', $fields['name']['default']);
        // L'étape « Langue » d'abord, comme une offre (décision 2026-11-24-mes-partenaires-cartes).
        $this->assertSame(['Langue', ['content_locale']], [$form['steps'][0]['title'], $form['steps'][0]['fields']]);
        $this->assertSame('fr', $fields['content_locale']['default']);
        $this->assertTrue($fields['content_locale']['reloads']);

        // En anglais : le formulaire pré-rempli en anglais, l'envoi n'écrit que l'anglais.
        $english = collect($this->asUser($user)->getJson("/api/v1/forms/partner.partner?record={$partner->getKey()}&content_locale=en")->json('data.fields'))->keyBy('name');
        $this->assertSame('Before', $english['name']['default']);
        $this->asUser($user)->patchJson("/api/v1/partners/{$partner->getKey()}", ['content_locale' => 'en', 'name' => 'After', 'description' => 'Text.', 'logo' => []])->assertNoContent();
        $this->assertSame(['Avant', 'After'], [$partner->fresh()->getTranslation('name', 'fr'), $partner->fresh()->getTranslation('name', 'en')]);

        $this->asUser($user)->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Après', 'description' => 'Texte.', 'logo' => []])->assertNoContent();
        $partner->refresh();
        // Seule la langue du compte change ; il repasse en validation.
        $this->assertSame(['Après', 'After', PartnerStatusEnum::PENDING], [$partner->getTranslation('name', 'fr'), $partner->getTranslation('name', 'en'), $partner->status]);
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
        $this->asUser($helper)->patchJson("/api/v1/partners/{$partner->getKey()}", ['name' => 'Modifié', 'description' => 'Texte.'])->assertNoContent();
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
