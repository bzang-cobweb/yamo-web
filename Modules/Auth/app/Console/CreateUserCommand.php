<?php

namespace Modules\Auth\Console;

use Bza\Form\Support\Locales;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Crée un compte en ligne de commande. Remplace `make:filament-user`, qui
 * attend une colonne `name` absente de ce projet.
 */
class CreateUserCommand extends Command
{
    protected $signature = 'auth:create-user
        {--name= : Pseudonyme (unique)}
        {--first-name= : Prénom}
        {--last-name= : Nom}
        {--email= : Adresse e-mail (e-mail et/ou téléphone)}
        {--phone= : Numéro de téléphone}
        {--phone-country= : Code ISO du pays du téléphone (ex. CH)}
        {--password= : Mot de passe (demandé de façon masquée s\'il est omis)}
        {--locale= : Langue préférée}
        {--super-admin : Attribuer le rôle super administrateur}';

    protected $description = 'Créer un utilisateur, éventuellement super administrateur';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?? text(__('auth::user.name'), required: true),
            'first_name' => $this->option('first-name') ?? text(__('auth::user.first_name'), required: true),
            'last_name' => $this->option('last-name') ?? text(__('auth::user.last_name'), required: true),
            // Avec --phone, l'e-mail est facultatif et n'est pas demandé ; il ne
            // l'est jamais si l'application n'a pas le canal e-mail.
            'email' => $this->option('email') ?? ($this->option('phone') !== null || ! AuthChannels::email() ? null : (text(__('auth::user.email')) ?: null)),
            'phone' => $this->option('phone'),
            'phone_country' => $this->option('phone-country') !== null ? strtoupper($this->option('phone-country')) : null,
            'password' => $this->option('password') ?? password(__('auth::user.password'), required: true),
            'locale' => $this->option('locale') ?? select(__('auth::user.locale'), Locales::options(), default: Locales::default()),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            // Un compte a un e-mail et/ou un téléphone, jamais aucun des deux, et
            // seulement sur les canaux actifs de l'application.
            'email' => AuthChannels::email()
                ? ['nullable', AuthChannels::phone() ? 'required_without:phone' : 'required', 'email', 'max:255', Rule::unique(User::class)]
                : ['prohibited'],
            'phone' => AuthChannels::phone()
                ? ['nullable', AuthChannels::email() ? 'required_without:email' : 'required', 'string', 'phone']
                : ['prohibited'],
            'phone_country' => ['nullable', 'required_with:phone', Rule::exists('countries', 'code')],
            'password' => ['required', 'string', 'max:255'],
            'locale' => ['required', Rule::in(Locales::values())],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();
        $country = PhoneNumbers::country($data['phone_country'] ?? null);
        unset($data['phone_country']);

        if (isset($data['phone'])) {
            $data['phone'] = PhoneNumbers::toE164($data['phone'], (string) $country?->code);
            $data['phone_country_id'] = $country?->getKey();
        }

        // Compte créé par un administrateur : ses identifiants sont tenus pour vérifiés.
        $user = User::create($data);
        $user->forceFill([
            'email_verified_at' => $user->email !== null ? now() : null,
            'phone_verified_at' => $user->phone !== null ? now() : null,
        ])->save();

        if ($this->option('super-admin')) {
            $user->assignRole(RoleEnum::SUPER_ADMIN);
        }

        $this->components->info(__('auth::user.created', ['name' => $user->full_name, 'email' => $user->email ?? $user->phone]));

        return self::SUCCESS;
    }
}
