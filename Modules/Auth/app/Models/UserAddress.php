<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Database\Factories\UserAddressFactory;
use Modules\Auth\Enums\GenderEnum;
use Modules\Location\Models\Address;

/**
 * Une adresse d'un compte : l'adresse partagée (`Address`, module Location) et
 * son destinataire et ses indications (« derrière le marché, portail bleu »),
 * propre à ce compte (décisions 2026-11-18-adresse-du-compte-modele et
 * 2026-11-18-useraddress-dans-auth). Un compte
 * peut viser la même adresse plusieurs fois, pour des destinataires différents.
 *
 * @property int $user_id
 * @property int $address_id
 * @property GenderEnum|null $gender
 * @property string $first_name
 * @property string $last_name
 * @property string|null $indication
 * @property bool $is_residence l'adresse de résidence du compte, une seule (décision 2026-11-27-adresse-de-residence)
 * @property-read Address $address
 */
#[Table('user_addresses')]
#[Fillable(['user_id', 'address_id', 'gender', 'first_name', 'last_name', 'indication', 'is_residence'])]
#[UseFactory(UserAddressFactory::class)]
class UserAddress extends Model
{
    /** @use HasFactory<UserAddressFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => GenderEnum::class,
            'is_residence' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * « Jeanne Dupont ».
     */
    public function recipient(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
