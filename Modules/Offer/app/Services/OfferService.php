<?php

namespace Modules\Offer\Services;

use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Form\Enums\FieldType;
use Modules\Form\Support\Field;
use Modules\Location\Models\Country;
use Modules\Location\Services\AddressService;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;
use Modules\Offer\Models\OfferInformation;

/**
 * Créer, modifier, retirer une offre selon le domaine actif (décision
 * 2026-11-18-offres-et-domaines) : les champs communs vont dans leurs colonnes,
 * les attributs du domaine dans `details`. Une offre créée l'est dans le pays
 * courant, dont elle prend la devise (décision 2026-11-18-espace-pays).
 */
class OfferService
{
    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function create(User $user, Domain $domain, Country $country, array $data, ?string $locale = null): Offer
    {
        if ($domain->onePerAccount() && Offer::query()->where('user_id', $user->getKey())->where('country_id', $country->getKey())->exists()) {
            throw ValidationException::withMessages(['title' => __('offer::offer.one_per_account')]);
        }

        $locale ??= $user->preferredLocale();
        $offer = Offer::query()->create([
            'user_id' => $user->getKey(),
            'country_id' => $country->getKey(),
            // La langue d'origine (décision 2026-11-19-contrat-langue-saisie-allemand).
            'locale' => $locale,
            ...$this->columns($domain, $data, $country, $locale ?? $user->preferredLocale()),
        ]);
        $this->syncAddress($offer, $domain, $data, $country);
        $this->syncInformations($offer, $data, $locale ?? $user->preferredLocale());

        return $offer;
    }

    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function update(Offer $offer, Domain $domain, array $data, ?string $locale = null): Offer
    {
        $offer->fill($this->columns($domain, $data, $offer->country, $locale ?? $offer->user->preferredLocale(), $offer))->save();
        $this->syncAddress($offer, $domain, $data, $offer->country);
        $this->syncInformations($offer, $data, $locale ?? $offer->user->preferredLocale());

        return $offer->refresh();
    }

    /**
     * Retrait : suppression douce, l'offre reste récupérable dans le back-office.
     */
    public function delete(Offer $offer): void
    {
        $offer->delete();
    }

    /**
     * Les attributs saisis dans le back-office, typés comme ceux de l'API.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function typedDetails(Domain $domain, array $details): array
    {
        foreach ($domain->attributes() as $field) {
            if (array_key_exists($field->name, $details)) {
                $details[$field->name] = $this->value($field, $details[$field->name]);
            }
        }

        return $details;
    }

    /**
     * Les traductions d'un texte, celle de `$locale` remplacée ; un texte vide la retire.
     *
     * @param  array<string, mixed>  $translations
     * @return array<string, string>
     */
    private function translations(array $translations, string $locale, mixed $value): array
    {
        $translations[$locale] = $value;

        return array_filter($translations, fn (mixed $text): bool => filled($text));
    }

    /**
     * Les blocs d'informations (décision 2026-11-19-contrat-offer-informations), dans
     * l'ordre envoyé : un bloc connu (`id`) est mis à jour dans la langue `$locale`,
     * ses autres langues gardées ; un nouveau est créé ; un bloc absent est retiré.
     * Sans le champ envoyé (ancien envoi), rien ne change.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncInformations(Offer $offer, array $data, string $locale): void
    {
        if (! array_key_exists('informations', $data)) {
            return;
        }

        $existing = $offer->informations()->get()->keyBy(fn (OfferInformation $information): string => (string) $information->getKey());
        $kept = [];

        // Les données validées gardent l'indice de chaque bloc, pas toujours leur ordre.
        $items = (array) $data['informations'];
        ksort($items);

        foreach (array_values($items) as $position => $item) {
            $item = (array) $item;
            $information = $existing->get((string) ($item['id'] ?? '')) ?? new OfferInformation(['offer_id' => $offer->getKey()]);
            $information->setTranslation('title', $locale, trim((string) $item['title']));
            $information->setTranslation('description', $locale, trim((string) $item['description']));
            $information->position = $position;
            $information->save();
            $kept[] = $information->getKey();
        }

        $offer->informations()->whereNotIn('id', $kept)->delete();
        $offer->unsetRelation('informations');
    }

    /**
     * L'adresse de l'offre et son indication, dans `offer_addresses` (décision
     * 2026-11-19-contrat-adresse-offre-table). Sans adresse, la ligne est retirée,
     * indication comprise. Sans le champ `indication` envoyé (ancien envoi),
     * l'indication actuelle reste ; un champ vide la retire.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncAddress(Offer $offer, Domain $domain, array $data, ?Country $country): void
    {
        $current = $offer->offerAddress;
        $addressId = $domain->hasAddress() ? $this->addressId($data, $country, $current) : null;

        if ($addressId === null) {
            $current?->delete();
            $offer->unsetRelation('offerAddress');

            return;
        }

        $indication = array_key_exists('indication', $data)
            ? (filled($data['indication']) ? trim((string) $data['indication']) : null)
            : $current?->indication;

        $offer->setRelation('offerAddress', $offer->offerAddress()->updateOrCreate([], ['address_id' => $addressId, 'indication' => $indication]));
        $offer->unsetRelation('address');
    }

    /**
     * L'adresse saisie (décision 2026-11-19-contrat-adresse-offre-recherche) : une
     * adresse de même empreinte dans le pays est reprise, sinon elle est créée ;
     * rien n'est ajouté au carnet du compte. Sans rue ni ville saisies : aucune
     * adresse ; sans les champs (ancien envoi) : l'adresse actuelle de l'offre.
     *
     * @param  array<string, mixed>  $data
     */
    private function addressId(array $data, ?Country $country, ?OfferAddress $current): ?int
    {
        if (! array_key_exists('street', $data) && ! array_key_exists('city', $data)) {
            return $current?->address_id;
        }

        $street = trim((string) ($data['street'] ?? ''));
        $zip = trim((string) ($data['zip'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));

        if ($street === '' || $city === '' || $country === null) {
            return null;
        }

        // La même règle que partout : une adresse unique par pays (`AddressService`).
        return (int) app(AddressService::class)->findOrCreate(['country_id' => $country->getKey(), 'street' => $street, 'zip' => $zip, 'city' => $city])->getKey();
    }

    /**
     * Un nombre saisi est gardé comme nombre (64, 3.5), pas comme texte.
     */
    private function value(Field $field, mixed $value): mixed
    {
        return $field->type === FieldType::NUMBER && is_numeric($value) ? $value + 0 : $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function columns(Domain $domain, array $data, ?Country $country, string $locale, ?Offer $offer = null): array
    {
        $price = $domain->hasPrice() ? ($data['price'] ?? null) : null;

        return [
            // Textes saisis dans la langue `$locale` ; les autres langues sont gardées.
            'title' => $this->translations($offer?->getTranslations('title') ?? [], $locale, $data['title']),
            'description' => $this->translations($offer?->getTranslations('description') ?? [], $locale, $data['description'] ?? null),
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'],
            'price' => $price,
            'currency' => $price === null ? null : $country?->currency,
            // Attributs d'un autre domaine gardés tels quels ; ceux du domaine actif remplacés.
            'details' => [
                ...($offer?->details ?? []),
                ...collect($domain->attributes())->mapWithKeys(fn (Field $field): array => [$field->name => $field->isTranslatable()
                    ? $this->translations((array) ($offer?->detail($field->name) ?? []), $locale, $data[$field->name] ?? null)
                    : $this->value($field, $data[$field->name] ?? null)])->all(),
            ],
        ];
    }
}
