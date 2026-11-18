<?php

namespace Modules\Offer\Services;

use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Form\Enums\FieldType;
use Modules\Form\Support\Field;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Models\Offer;

/**
 * Créer, modifier, retirer une offre selon le domaine actif (décision
 * 2026-11-18-offres-et-domaines) : les champs communs vont dans leurs colonnes,
 * les attributs du domaine dans `details`. La devise est celle du domaine.
 */
class OfferService
{
    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function create(User $user, Domain $domain, array $data): Offer
    {
        if ($domain->onePerAccount() && Offer::query()->where('user_id', $user->getKey())->exists()) {
            throw ValidationException::withMessages(['title' => __('offer::offer.one_per_account')]);
        }

        return Offer::query()->create(['user_id' => $user->getKey(), ...$this->columns($domain, $data)]);
    }

    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function update(Offer $offer, Domain $domain, array $data): Offer
    {
        $offer->fill($this->columns($domain, $data, $offer))->save();

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
    private function columns(Domain $domain, array $data, ?Offer $offer = null): array
    {
        $price = $domain->hasPrice() ? ($data['price'] ?? null) : null;

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'address_id' => $domain->hasAddress() ? ($data['address_id'] ?? null) : null,
            'status' => $data['status'],
            'price' => $price,
            'currency' => $price === null ? null : $domain->currency(),
            // Attributs d'un autre domaine gardés tels quels ; ceux du domaine actif remplacés.
            'details' => [
                ...($offer?->details ?? []),
                ...collect($domain->attributes())->mapWithKeys(fn (Field $field): array => [$field->name => $this->value($field, $data[$field->name] ?? null)])->all(),
            ],
        ];
    }
}
