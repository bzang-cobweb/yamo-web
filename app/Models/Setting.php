<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Réglages de l'application, édités dans le back-office (page Application) et
 * servis par `GET /api/v1/app` : description traduite ; « Appliquer le paiement »
 * (`payment_required`, décision 2026-11-19-contrat-paiement-visibilite-avis) et pour les partenaires
 * (`partner_payment_required`, décision 2026-11-23-paiement-partenaires), non servis ;
 * âge minimum (`minimum_age`, décision 2026-11-19-age-minimum) ; nombres d'offres par ville et de partenaires
 * sur l'accueil (décision 2026-11-23-accueil-nombres-reglables). Une seule ligne, obtenue
 * par `Setting::current()`. Le logo n'est pas un réglage : ses fichiers sont
 * livrés avec le code (`public/images/logo`, et dans l'app mobile).
 */
#[Fillable(['description', 'payment_required', 'partner_payment_required', 'minimum_age', 'home_offers_per_city', 'home_partners'])]
class Setting extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_required' => 'boolean',
            'partner_payment_required' => 'boolean',
            'minimum_age' => 'integer',
            'home_offers_per_city' => 'integer',
            'home_partners' => 'integer',
        ];
    }

    /**
     * Le paiement est-il appliqué ? Alors une offre n'est visible des autres que
     * jusqu'à sa date d'expiration.
     */
    public static function paymentRequired(): bool
    {
        return (bool) self::query()->oldest('id')->value('payment_required');
    }

    /**
     * Le paiement des partenaires est-il appliqué ? Alors un partenaire n'est visible dans l'app que jusqu'à sa
     * date d'expiration ; sans date, il n'est pas visible (décision 2026-11-23-paiement-partenaires).
     */
    public static function partnerPaymentRequired(): bool
    {
        return (bool) self::query()->oldest('id')->value('partner_payment_required');
    }

    /**
     * L'âge minimum pour s'inscrire, ou `null` s'il n'y en a pas. Renseigné, la date
     * de naissance devient obligatoire et doit donner au moins cet âge.
     */
    public static function minimumAge(): ?int
    {
        $age = self::query()->oldest('id')->value('minimum_age');

        return $age === null ? null : (int) $age;
    }

    /** Sans réglage, 10 offres par ville et 10 partenaires sur l'accueil. */
    public const HOME_DEFAULT_COUNT = 10;

    /**
     * Combien d'offres par ville dans les carrousels de l'accueil (décision 2026-11-23-accueil-nombres-reglables).
     */
    public static function homeOffersPerCity(): int
    {
        return (int) (self::query()->oldest('id')->value('home_offers_per_city') ?? self::HOME_DEFAULT_COUNT);
    }

    /**
     * Combien de partenaires, tirés au hasard, dans le carrousel de l'accueil (décision
     * 2026-11-23-accueil-nombres-reglables).
     */
    public static function homePartners(): int
    {
        return (int) (self::query()->oldest('id')->value('home_partners') ?? self::HOME_DEFAULT_COUNT);
    }

    /**
     * La ligne des réglages, créée vide au premier besoin.
     */
    public static function current(): self
    {
        $setting = self::query()->oldest('id')->firstOrCreate();
        // Une lecture, pas une création : une réponse d'API reste un 200.
        $setting->wasRecentlyCreated = false;

        return $setting;
    }

    /**
     * Description dans la langue courante (repli sur la langue par défaut), ou
     * `null` si aucune n'est saisie.
     */
    public function localizedDescription(): ?string
    {
        $description = (string) $this->getTranslation('description', app()->getLocale());

        return $description === '' ? null : $description;
    }
}
