<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Location\Models\Country;

/**
 * Un numéro supplémentaire d'un compte (décision 2026-11-27-plusieurs-numeros). Le principal reste `users.phone` (connexion
 * par SMS, codes, notifications) ; celui-ci sert de contact. En E.164 (`PhoneNumbers`), avec le pays de son indicatif ;
 * vérifié par un code SMS à l'ajout.
 *
 * @property int $user_id
 * @property string $phone
 * @property int|null $phone_country_id
 * @property Carbon|null $verified_at
 * @property-read User $user
 * @property-read Country|null $phoneCountry
 */
#[Table('user_phones')]
#[Fillable(['phone', 'phone_country_id', 'verified_at'])]
class UserPhone extends Model
{
    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pays de l'indicatif du numéro.
     *
     * @return BelongsTo<Country, $this>
     */
    public function phoneCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_country_id');
    }
}
