<?php

namespace Modules\Location\Lists;

use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Forms\AddressForm;
use Modules\Location\Models\Address;

/**
 * « Mes adresses », ouverte depuis le menu du profil : les adresses du compte,
 * chacune modifiable dans `location.address` et retirable (décision
 * 2026-11-18-adresses-liste-generique).
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

    public function items(): array
    {
        $user = $this->user instanceof User ? $this->user : null;

        if ($user === null) {
            return [];
        }

        return $user->addresses()->with('country')->get()
            ->map(fn (Address $address): ListItem => ListItem::make($address->getKey(), $address->title(), $address->subtitle())
                ->edit(AddressForm::name())
                ->delete(
                    __('location::address.detach'),
                    __('location::address.list.confirm'),
                    Submission::toRoute(
                        'api.v1.auth.me.addresses.destroy',
                        SuccessAction::BACK,
                        __('location::address.list.detached'),
                        destructive: true,
                        parameters: ['address' => $address->getKey()],
                    ),
                ))
            ->values()
            ->all();
    }
}
