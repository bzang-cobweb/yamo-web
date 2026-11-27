<?php

namespace Modules\Auth\Lists;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Listing;
use Bza\Form\Support\ListItem;
use Bza\Form\Support\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Forms\UserAddressForm;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Modules\Location\Support\CurrentCountry;

/**
 * « Mes adresses », ouverte depuis le menu du profil : les adresses du compte
 * (« Jeanne Dupont », « Rue du Lac 4, 1200 Genève, Suisse »), chacune modifiable
 * dans `auth.address` et retirable (décisions
 * 2026-11-18-adresses-liste-generique et 2026-11-18-adresse-du-compte-modele).
 */
class UserAddressListing extends Listing
{
    public static function name(): string
    {
        return 'auth.addresses';
    }

    public function title(): string
    {
        return __('auth::address.list.title');
    }

    public function emptyText(): string
    {
        return __('auth::address.list.empty');
    }

    /**
     * « Ajouter une adresse » : un bouton dans la barre fixe du bas, plus de lien sous la
     * recherche (décision 2026-11-21-ajout-adresse-barre-du-bas).
     *
     * @return array{label: string, form: string, link: null}
     */
    public function footer(): array
    {
        return ['label' => __('auth::address.list.create'), 'form' => UserAddressForm::name(), 'link' => null];
    }

    /**
     * Les adresses du compte, dans l'ordre de création ; aucune sans compte.
     *
     * @return HasMany<UserAddress, User>|Builder<UserAddress>
     */
    public function query(): HasMany|Builder
    {
        if (! $this->user instanceof User) {
            return UserAddress::query()->whereRaw('1 = 0')->orderBy('id');
        }

        // Celles du pays courant (décision 2026-11-18-espace-pays).
        return $this->user->addresses()->with('address.country')
            ->whereHas('address', fn (Builder $query) => $query->inCountry(CurrentCountry::require()->getKey()));
    }

    public function searchPlaceholder(): string
    {
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
    }

    /**
     * Le destinataire (prénom, nom) ou l'adresse (rue, NPA, ville) contient le
     * texte (décision 2026-11-18-recherche-dans-la-liste).
     */
    protected function search(Builder $query, string $term): void
    {
        $this->orWhereContains($query, ['first_name', 'last_name'], $term);
        $query->orWhereHas('address', fn (Builder $address) => $address->where(
            fn (Builder $columns) => $this->orWhereContains($columns, ['street', 'zip', 'city'], $term),
        ));
    }

    public function item(Model $record): ListItem
    {
        /** @var UserAddress $record */
        return ListItem::make(
            $record->getKey(),
            $record->recipient(),
            $record->address->street.', '.$record->address->subtitle(),
        )
            ->edit(UserAddressForm::name())
            ->delete(
                __('auth::address.detach'),
                __('auth::address.list.confirm'),
                Submission::toRoute(
                    'api.v1.auth.me.addresses.destroy',
                    SuccessAction::BACK,
                    __('auth::address.list.detached'),
                    destructive: true,
                    parameters: ['userAddress' => $record->getKey()],
                ),
            );
    }
}
