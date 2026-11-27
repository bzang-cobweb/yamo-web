<?php

namespace Modules\Auth\Lists;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Listing;
use Bza\Form\Support\ListItem;
use Bza\Form\Support\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Forms\PhoneChangeForm;
use Modules\Auth\Forms\PhoneVerifyForm;
use Modules\Auth\Forms\UserPhoneForm;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;
use Modules\Auth\Support\Masks;
use Modules\Location\Models\Country;

/**
 * « Mes numéros », ouverte depuis « Informations personnelles » (décision 2026-11-27-plusieurs-numeros) : d'abord le
 * numéro principal (`users.phone`, épinglé en tête : « Principal », « Changer »), puis les numéros supplémentaires
 * (`user_phones`), chacun « Rendre principal » et retirable. Les numéros sont masqués (décision
 * 2026-11-27-email-telephone-masques).
 */
class UserPhoneListing extends Listing
{
    /** L'identifiant de la carte du numéro principal, qui n'est pas une ligne de `user_phones`. */
    public const PRIMARY = 'primary';

    public static function name(): string
    {
        return 'auth.phones';
    }

    public function title(): string
    {
        return __('auth::phones.list.title');
    }

    /**
     * « Ajouter un numéro » : un bouton dans la barre fixe du bas, comme « Mes adresses ».
     *
     * @return array{label: string, form: string, link: null}
     */
    public function footer(): array
    {
        return ['label' => __('auth::phones.list.create'), 'form' => UserPhoneForm::name(), 'link' => null];
    }

    /**
     * Les numéros supplémentaires du compte, dans l'ordre d'ajout ; aucun sans compte.
     *
     * @return HasMany<UserPhone, User>|Builder<UserPhone>
     */
    public function query(): HasMany|Builder
    {
        if (! $this->user instanceof User) {
            return UserPhone::query()->whereRaw('1 = 0')->orderBy('id');
        }

        return $this->user->phones()->with('phoneCountry');
    }

    /**
     * Le compte lui-même, en tête : sa carte est celle du numéro principal.
     *
     * @return Builder<User>|null
     */
    protected function pinned(): ?Builder
    {
        return $this->user instanceof User
            ? User::query()->whereKey($this->user->getKey())->with('phoneCountry')
            : null;
    }

    public function item(Model $record): ListItem
    {
        return $record instanceof User ? $this->primary($record) : $this->extra($record);
    }

    /**
     * Le numéro principal : « Changer » ouvre le formulaire existant (code au nouveau numéro) ; non vérifié,
     * « Vérifier » renvoie le code. Sans numéro : « Ajouter ».
     */
    private function primary(User $user): ListItem
    {
        if (blank($user->phone)) {
            return ListItem::make(self::PRIMARY, __('auth::phones.list.no_primary'))
                ->tag(__('auth::phones.list.primary'), 'success')
                ->action(__('auth::phones.list.add_primary'), 'primary', form: PhoneChangeForm::name());
        }

        $item = ListItem::make(self::PRIMARY, Masks::phone($user->phone), $this->subtitle($user->phoneCountry, $user->phone_verified_at !== null))
            ->tag(__('auth::phones.list.primary'), 'success')
            ->action(__('auth::phones.list.change'), form: PhoneChangeForm::name());

        return $user->phone_verified_at === null
            ? $item->action(__('auth::phones.list.verify'), form: PhoneVerifyForm::name())
            : $item;
    }

    /**
     * Un numéro supplémentaire : « Rendre principal » (après confirmation), et retirable.
     */
    private function extra(Model $record): ListItem
    {
        /** @var UserPhone $record */
        return ListItem::make($record->getKey(), Masks::phone($record->phone), $this->subtitle($record->phoneCountry, $record->verified_at !== null))
            ->action(
                __('auth::phones.list.make_primary'),
                confirm: __('auth::phones.list.make_primary_confirm'),
                submission: Submission::toRoute(
                    'api.v1.auth.me.phones.primary',
                    SuccessAction::BACK,
                    __('auth::phones.list.made_primary'),
                    parameters: ['userPhone' => $record->getKey()],
                ),
            )
            ->delete(
                __('auth::phones.list.remove'),
                __('auth::phones.list.remove_confirm'),
                Submission::toRoute(
                    'api.v1.auth.me.phones.destroy',
                    SuccessAction::BACK,
                    __('auth::phones.list.removed'),
                    destructive: true,
                    parameters: ['userPhone' => $record->getKey()],
                ),
            );
    }

    /**
     * Le pays de l'indicatif, puis « non vérifié » s'il y a lieu.
     */
    private function subtitle(?Country $country, bool $verified): ?string
    {
        $parts = array_filter([$country?->label(), $verified ? null : __('auth::phones.list.not_verified')]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
