<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\Country;

/**
 * Le contact d'urgence d'un compte (décision 2026-11-27-contact-d-urgence) : un seul par compte. Le téléphone est
 * enregistré en E.164 (`PhoneNumbers`), avec le pays de son indicatif ; `locale` est la langue dans laquelle lui parler.
 *
 * @property int $user_id
 * @property string $name
 * @property string|null $relationship
 * @property string|null $email
 * @property string $phone
 * @property int|null $phone_country_id
 * @property string $locale
 * @property-read Country|null $phoneCountry
 */
#[Table('emergency_contacts')]
#[Fillable(['name', 'relationship', 'email', 'phone', 'phone_country_id', 'locale'])]
class EmergencyContact extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pays de l'indicatif du téléphone.
     *
     * @return BelongsTo<Country, $this>
     */
    public function phoneCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_country_id');
    }
}
