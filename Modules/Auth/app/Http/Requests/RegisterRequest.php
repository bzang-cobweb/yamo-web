<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Auth\Forms\RegisterForm;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Créer un compte : règles du formulaire `auth.register`, puis unicité du
 * pseudonyme, de l'e-mail et du téléphone (comparé en E.164).
 */
class RegisterRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): RegisterForm
    {
        return new RegisterForm;
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

            if (User::query()->where('name', $data['name'])->exists()) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => __('auth::user.name')]));
            }

            if (filled($data['email'] ?? null) && User::query()->where('email', $data['email'])->exists()) {
                $validator->errors()->add('email', __('validation.unique', ['attribute' => __('auth::user.email')]));
            }

            if (filled($data['phone'] ?? null)) {
                $phone = PhoneNumbers::toE164($data['phone'], (string) $data['phone_country']);

                if (User::query()->where('phone', $phone)->exists()) {
                    $validator->errors()->add('phone', __('validation.unique', ['attribute' => __('auth::user.phone')]));
                }
            }
        }];
    }
}
