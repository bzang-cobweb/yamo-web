<?php

namespace Modules\Location\Lists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Forms\AddressForm;
use Modules\Location\Models\UserAddress;

/**
 * « Mes adresses », ouverte depuis le menu du profil : les adresses du compte
 * (« Jeanne Dupont », « Rue du Lac 4, 1200 Genève, Suisse »), chacune modifiable
 * dans `location.address` et retirable (décisions
 * 2026-11-18-adresses-liste-generique et 2026-11-18-adresse-du-compte-modele).
 */
class AddressListing extends Listing
{
    public static function name(): string
    {
        return 'location.addresses';
    }

    public function title(): string
    {
        return __('location::address.list.title');
    }

    public function emptyText(): string
    {
        return __('location::address.list.empty');
    }

    public function create(): array
    {
        return ['label' => __('location::address.list.create'), 'form' => AddressForm::name()];
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

        return $this->user->addresses()->with('address.country');
    }

    public function item(Model $record): ListItem
    {
        /** @var UserAddress $record */
        return ListItem::make(
            $record->getKey(),
            $record->recipient(),
            $record->address->street.', '.$record->address->subtitle(),
        )
            ->edit(AddressForm::name())
            ->delete(
                __('location::address.detach'),
                __('location::address.list.confirm'),
                Submission::toRoute(
                    'api.v1.auth.me.addresses.destroy',
                    SuccessAction::BACK,
                    __('location::address.list.detached'),
                    destructive: true,
                    parameters: ['userAddress' => $record->getKey()],
                ),
            );
    }
}
