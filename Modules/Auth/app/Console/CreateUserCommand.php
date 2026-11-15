<?php

namespace Modules\Auth\Console;

use App\Support\Locales;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;

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
        {--first-name= : Prénom}
        {--last-name= : Nom}
        {--email= : Adresse e-mail}
        {--password= : Mot de passe (demandé de façon masquée s\'il est omis)}
        {--locale= : Langue préférée}
        {--super-admin : Attribuer le rôle super administrateur}';

    protected $description = 'Créer un utilisateur, éventuellement super administrateur';

    public function handle(): int
    {
        $data = [
            'first_name' => $this->option('first-name') ?? text(__('auth::user.first_name'), required: true),
            'last_name' => $this->option('last-name') ?? text(__('auth::user.last_name'), required: true),
            'email' => $this->option('email') ?? text(__('auth::user.email'), required: true),
            'password' => $this->option('password') ?? password(__('auth::user.password'), required: true),
            'locale' => $this->option('locale') ?? select(__('auth::user.locale'), Locales::options(), default: config('app.locale')),
        ];

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'max:255'],
            'locale' => ['required', Rule::in(Locales::values())],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($validator->validated());
        $user->forceFill(['email_verified_at' => now()])->save();

        if ($this->option('super-admin')) {
            $user->assignRole(RoleEnum::SUPER_ADMIN);
        }

        $this->components->info(__('auth::user.created', ['name' => $user->full_name, 'email' => $user->email]));

        return self::SUCCESS;
    }
}
