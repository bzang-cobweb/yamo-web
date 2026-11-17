<?php

namespace Modules\Auth\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Auth\Console\CreateUserCommand;
use Modules\Auth\Console\PurgeDeletedAccountsCommand;
use Modules\Auth\Forms\CodeForm;
use Modules\Auth\Forms\DeleteAccountForm;
use Modules\Auth\Forms\EmailChangeForm;
use Modules\Auth\Forms\ForgotPasswordForm;
use Modules\Auth\Forms\LoginForm;
use Modules\Auth\Forms\PasswordForm;
use Modules\Auth\Forms\PersonalInfoForm;
use Modules\Auth\Forms\PhoneChangeForm;
use Modules\Auth\Forms\PreferencesForm;
use Modules\Auth\Forms\RegisterForm;
use Modules\Auth\Forms\ResetPasswordForm;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\ProfileMenuLink;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\PermissionPolicy;
use Modules\Auth\Policies\ProfileMenuLinkPolicy;
use Modules\Auth\Policies\RolePolicy;
use Modules\Auth\Policies\UserPolicy;
use Modules\Auth\Sms\LogSmsSender;
use Modules\Auth\Sms\SmsSender;
use Modules\Form\Support\FormRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    /**
     * SMS écrits dans le journal tant qu'aucun fournisseur n'est choisi.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [SmsSender::class => LogSmsSender::class];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(ProfileMenuLink::class, ProfileMenuLinkPolicy::class);

        // Formulaires servis à l'app mobile (module Form).
        $registry = $this->app->make(FormRegistry::class);
        $registry->register(LoginForm::class);
        $registry->register(PersonalInfoForm::class);
        $registry->register(PasswordForm::class);
        $registry->register(DeleteAccountForm::class);
        $registry->register(PreferencesForm::class);
        $registry->register(RegisterForm::class);
        $registry->register(CodeForm::class);
        $registry->register(EmailChangeForm::class);
        $registry->register(PhoneChangeForm::class);
        $registry->register(ForgotPasswordForm::class);
        $registry->register(ResetPasswordForm::class);

        // Connexion API : 5 essais par minute pour un même identifiant (e-mail ou
        // numéro) depuis une même adresse.
        RateLimiter::for('auth-login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower((string) ($request->input('email') ?: $request->input('phone_country').$request->input('phone'))).'|'.$request->ip()));
        // Inscription : 5 demandes par minute depuis une même adresse.
        RateLimiter::for('auth-register', fn (Request $request): Limit => Limit::perMinute(5)->by((string) $request->ip()));
        // Codes (envoi, validation, renvoi) : 10 appels par minute depuis une même adresse.
        RateLimiter::for('auth-codes', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->ip()));
    }

    /**
     * The name of the module.
     */
    protected string $name = 'Auth';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'auth';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        CreateUserCommand::class,
        PurgeDeletedAccountsCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     */
    /**
     * Purge quotidienne des comptes supprimés dont le délai de restauration est passé.
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command(PurgeDeletedAccountsCommand::class)->daily();
    }
}
