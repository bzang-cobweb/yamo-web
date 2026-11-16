<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Auth\Forms\PhoneChangeForm;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Ajouter ou changer son téléphone : règles du formulaire `auth.profile.phone`
 * (numéro valide pour le pays choisi), et un numéro que personne d'autre n'a.
 */
class PhoneChangeRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateMe', User::class) ?? false;
    }

    protected function form(): PhoneChangeForm
    {
        return new PhoneChangeForm($this->user());
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $validator->validated();
            $phone = PhoneNumbers::toE164($data['phone'], (string) $data['phone_country']);

            if (User::query()->where('phone', $phone)->whereKeyNot($this->user()?->getKey())->exists()) {
                $validator->errors()->add('phone', __('validation.unique', ['attribute' => __('auth::user.phone')]));
            }
        }];
    }
}
