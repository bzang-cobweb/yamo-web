<?php

namespace Modules\Form\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Modules\Form\Rules\NoContactDetails;
use Modules\Form\Support\ContactDetails;
use Modules\Form\Support\Field;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Coordonnées dans un texte publié (décision 2026-11-19-contrat-annonces-sans-coordonnees) :
 * ce qui est refusé, et les textes ordinaires d'une annonce, qui passent.
 */
class ContactDetailsTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function refused(): array
    {
        return [
            'téléphone camerounais' => ['Appelez le 699 12 34 56 pour visiter', 'phone'],
            'téléphone avec indicatif' => ['+237 6 99-12-34-56', 'phone'],
            'téléphone avec 00' => ['00237699123456', 'phone'],
            'téléphone collé' => ['Studio dispo 699123456', 'phone'],
            'téléphone à points' => ['Tél. 699.12.34.56', 'phone'],
            'téléphone suisse' => ['079 123 45 67', 'phone'],
            'téléphone à barres obliques' => ['699/12/34/56', 'phone'],
            'téléphone à soulignés' => ['699_12_34_56', 'phone'],
            'lettre O pour zéro' => ['6O 9O 12 34 56', 'phone'],
            'chiffres en lettres' => ['six neuf neuf un deux trois quatre', 'spelled_phone'],
            'chiffres mêlés' => ['six neuf neuf 12 34 56', 'spelled_phone'],
            'chiffres en anglais' => ['six nine nine one two three four', 'spelled_phone'],
            'e-mail' => ['Écrire à awa.ngono@gmail.com', 'email'],
            'e-mail en majuscules' => ['AWA@GMAIL.COM', 'email'],
            'e-mail arobase' => ['awa arobase gmail point com', 'email'],
            'e-mail (at)' => ['awa (at) gmail (dot) com', 'email'],
            'site https' => ['Photos sur https://exemple.org/studio', 'link'],
            'site www' => ['Voir www.monstudio.cm', 'link'],
            'nom de domaine' => ['Réservez sur monstudio.com', 'link'],
            'lien whatsapp' => ['wa.me/237699123456', 'link'],
            'lien telegram' => ['t.me/awa', 'link'],
            'whatsapp' => ['Contact par WhatsApp uniquement', 'network'],
            'wtsp' => ['Dispo sur wtsp', 'network'],
            'whats app en deux mots' => ['écrivez sur whats app', 'network'],
            'facebook' => ['Plus de photos sur Facebook', 'network'],
            'insta' => ['Suivez-nous sur insta', 'network'],
            'telegram' => ['Telegram : awa', 'network'],
            'appelez-moi' => ['Appelez-moi pour visiter', 'phrase'],
            'contactez-nous' => ['Contactez nous pour visiter', 'phrase'],
            'me contacter' => ['Pour visiter, me contacter', 'phrase'],
            'mon numéro' => ['Mon numéro est en bas', 'phrase'],
            'joignable au' => ['Joignable au six neuf', 'phrase'],
            'call me' => ['Call me for a visit', 'phrase'],
            'my number' => ['My number is below', 'phrase'],
            'pseudonyme' => ['Mon compte : @awa_ngono', 'handle'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowed(): array
    {
        return [
            'pièces et surface' => ['3 chambres, 85 m², 2ᵉ étage'],
            'loyer et caution' => ['Loyer 250 000 FCFA, caution 2 mois'],
            'gros montant en FCFA' => ['Prix de vente 125 000 000 FCFA'],
            'montant en XAF' => ['1 200 000 XAF par an'],
            'montant en francs' => ['Caution 1 500 000 francs'],
            'année' => ['Disponible en 2026'],
            'deux années' => ['Bail 2026-2027'],
            'date' => ['Libre dès le 01.12.2026'],
            'date avec espaces' => ['Libre dès le 01 12 2026'],
            'date à barres obliques' => ['Libre dès le 01/12/2026'],
            'deux années à barre' => ['Saison 2026/2027'],
            'snap' => ['Un snap sur la cuisine : bien équipée'],
            'mot avec o près d’un chiffre' => ['Studio 2 pièces, loyer 80 000 FCFA, 4 occupants'],
            'université' => ['Proche de l’université de Douala'],
            'eau chaude' => ['Climatisé, eau chaude 24h/24, 7j/7'],
            'nombres en lettres courts' => ['Deux chambres, trois salles d’eau, un salon'],
            'texte anglais' => ['Bright flat at Akwa, close to shops. Quiet street.'],
            'mot proche' => ['Appartement très lumineux, signalé par un panneau'],
            'point de vue' => ['Belle vue, point de départ idéal'],
        ];
    }

    #[DataProvider('refused')]
    public function test_contact_details_are_found(string $text, string $kind): void
    {
        $this->assertSame($kind, ContactDetails::find($text), $text);
    }

    #[DataProvider('allowed')]
    public function test_ordinary_texts_pass(string $text): void
    {
        $this->assertNull(ContactDetails::find($text), $text);
    }

    public function test_the_rule_fails_with_the_message_in_the_request_language(): void
    {
        app()->setLocale('fr');
        $errors = Validator::make(['title' => 'Studio, tél 699 12 34 56'], ['title' => [new NoContactDetails]])->errors();

        $this->assertSame(['Retirez les coordonnées (téléphone, e-mail, site, réseau social) : elles ne sont pas autorisées dans une annonce.'], $errors->get('title'));
        $this->assertTrue(Validator::make(['title' => 'Studio meublé'], ['title' => [new NoContactDetails]])->passes());
    }

    public function test_a_field_opts_in_and_only_texts_can(): void
    {
        $rules = Field::text('title')->withoutContactDetails()->validationRules()['title'];
        $this->assertNotEmpty(array_filter($rules, fn (mixed $rule): bool => $rule instanceof NoContactDetails));
        $this->assertEmpty(array_filter(Field::text('title')->validationRules()['title'], fn (mixed $rule): bool => $rule instanceof NoContactDetails));

        $this->expectException(\LogicException::class);
        Field::number('surface')->withoutContactDetails();
    }

    public function test_a_word_added_to_the_list_is_refused_without_code(): void
    {
        $this->assertNull(ContactDetails::find('Plus de photos sur Wechat'));

        app('translator')->addLines(['contact.networks' => ['wechat']], 'fr', 'form');

        $this->assertSame('network', ContactDetails::find('Plus de photos sur Wechat'));
    }
}
