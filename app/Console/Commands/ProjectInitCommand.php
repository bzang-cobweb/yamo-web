<?php

namespace App\Console\Commands;

use App\Support\EnvFile;
use Bza\Form\Support\Locales;
use Illuminate\Console\Command;
use libphonenumber\PhoneNumberUtil;
use Modules\Location\Models\Country;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Nwidart\Modules\Facades\Module;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Initialise le projet pour un domaine (décision 2026-11-18-initialisation-par-domaine) :
 * tout est vérifié avant d'écrire ; seules les clés non secrètes du `.env` sont
 * écrites (`EnvFile::WRITABLE`) ; puis modules, migrations, données de départ,
 * pays actifs et, sur demande, premier super administrateur. Rejouable.
 */
class ProjectInitCommand extends Command
{
    protected $signature = 'project:init
        {--domain= : Domaine du projet (voir --list)}
        {--name= : Nom de l\'application}
        {--locale= : Langue par défaut}
        {--country=* : Pays actifs (codes ISO, ex. CH), un ou plusieurs, ou séparés par des virgules}
        {--channels= : Canaux de compte : email, phone ou email,phone}
        {--admin-email= : Crée le premier super administrateur avec cet e-mail}
        {--list : Lister les domaines connus}
        {--force : En production, ou pour changer le domaine d\'un projet qui a déjà des offres}';

    protected $description = 'Initialiser le projet pour un domaine (location, boutique…) : réglages, modules, données de départ';

    private const CHANNELS = ['email', 'phone'];

    public function handle(DomainRegistry $domains): int
    {
        if ($this->option('list')) {
            $this->table(['Domaine', 'Offre'], array_map(fn (string $key): array => [$key, $domains->make($key)->pluralLabel()], $domains->keys()));

            return self::SUCCESS;
        }

        if ($this->laravel->isProduction() && ! $this->option('force')) {
            $this->components->error('Production : relancer avec --force.');

            return self::FAILURE;
        }

        $settings = $this->settings($domains);
        $errors = $this->errors($settings, $domains);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->domainChangeAllowed($settings['domain'])) {
            return self::FAILURE;
        }

        // 1. Réglages non secrets du .env, puis la configuration de ce processus.
        $changed = EnvFile::current()->set([
            'APP_NAME' => $settings['name'],
            'APP_LOCALE' => $settings['locale'],
            'OFFER_DOMAIN' => $settings['domain'],
            'AUTH_CHANNEL_EMAIL' => in_array('email', $settings['channels'], true) ? 'true' : 'false',
            'AUTH_CHANNEL_PHONE' => in_array('phone', $settings['channels'], true) ? 'true' : 'false',
        ]);
        config([
            'app.name' => $settings['name'],
            'app.default_locale' => $settings['locale'],
            'offer.domain' => $settings['domain'],
            'auth.channels' => ['email' => in_array('email', $settings['channels'], true), 'phone' => in_array('phone', $settings['channels'], true)],
        ]);

        // 2. Modules requis par le domaine.
        $domain = $domains->make($settings['domain']);
        foreach ($domain->modules() as $name) {
            Module::findOrFail($name)->enable();
        }

        // 3. Base : migrations et données de départ (rôles, pays, catégories du domaine).
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        // 4. Pays actifs.
        Country::query()->update(['active' => false]);
        Country::query()->whereIn('code', $settings['countries'])->update(['active' => true]);

        // 5. Premier super administrateur, sur demande.
        if (filled($settings['admin_email'])) {
            $this->call('auth:create-user', ['--email' => $settings['admin_email'], '--locale' => $settings['locale'], '--super-admin' => true]);
        }

        $this->summary($settings, $changed);

        return self::SUCCESS;
    }

    /**
     * Les réglages : options, sinon questions (mode interactif).
     *
     * @return array{domain: string, name: string, locale: string, countries: list<string>, channels: list<string>, admin_email: string|null}
     */
    private function settings(DomainRegistry $domains): array
    {
        $interactive = $this->input->isInteractive();
        $countries = collect((array) $this->option('country'))
            ->flatMap(fn (string $codes): array => explode(',', $codes))
            ->map(fn (string $code): string => strtoupper(trim($code)))
            ->filter()->unique()->values()->all();
        $channels = $this->option('channels') === null ? [] : array_values(array_filter(array_map('trim', explode(',', (string) $this->option('channels')))));

        return [
            'domain' => (string) ($this->option('domain') ?? ($interactive ? select('Domaine du projet', array_combine($domains->keys(), array_map(fn (string $key): string => $key.' — '.$domains->make($key)->pluralLabel(), $domains->keys()))) : '')),
            'name' => (string) ($this->option('name') ?? ($interactive ? text('Nom de l\'application', default: (string) config('app.name'), required: true) : config('app.name'))),
            'locale' => (string) ($this->option('locale') ?? ($interactive ? select('Langue par défaut', Locales::options(), default: Locales::default()) : Locales::default())),
            'countries' => $countries !== [] ? $countries : ($interactive ? array_map('strtoupper', array_filter(array_map('trim', explode(',', text('Pays actifs (codes ISO séparés par des virgules)', default: 'CH', required: true))))) : []),
            'channels' => $channels !== [] ? $channels : ($interactive ? multiselect('Canaux de compte', ['email' => 'E-mail', 'phone' => 'Téléphone'], default: self::CHANNELS, required: true) : self::CHANNELS),
            'admin_email' => $this->option('admin-email') ?? ($interactive && confirm('Créer le premier super administrateur ?', default: false) ? text('E-mail du super administrateur', required: true) : null),
        ];
    }

    /**
     * Tout est vérifié avant d'écrire quoi que ce soit.
     *
     * @param  array{domain: string, name: string, locale: string, countries: list<string>, channels: list<string>, admin_email: string|null}  $settings
     * @return list<string>
     */
    private function errors(array $settings, DomainRegistry $domains): array
    {
        $regions = PhoneNumberUtil::getInstance()->getSupportedRegions();
        $errors = [];

        if (! $domains->has($settings['domain'])) {
            $errors[] = "Domaine inconnu : « {$settings['domain']} ». Connus : ".implode(', ', $domains->keys()).'.';
        }
        if (trim($settings['name']) === '') {
            $errors[] = 'Le nom de l\'application est vide.';
        }
        if (str_contains($settings['name'], '$') && (str_contains($settings['name'], "'") || str_contains($settings['name'], "\n"))) {
            $errors[] = 'Nom de l\'application refusé : un « $ » ne peut pas côtoyer une apostrophe ou un retour à la ligne.';
        }
        if (! in_array($settings['locale'], Locales::values(), true)) {
            $errors[] = "Langue inconnue : « {$settings['locale']} ». Possibles : ".implode(', ', Locales::values()).'.';
        }
        if ($settings['countries'] === []) {
            $errors[] = 'Au moins un pays actif est requis (--country=CH).';
        }
        foreach (array_diff($settings['countries'], $regions) as $code) {
            $errors[] = "Pays inconnu : « {$code} ».";
        }
        if ($settings['channels'] === [] || array_diff($settings['channels'], self::CHANNELS) !== []) {
            $errors[] = 'Canaux : email, phone ou email,phone.';
        }
        if (filled($settings['admin_email']) && filter_var($settings['admin_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = "E-mail invalide : « {$settings['admin_email']} ».";
        }
        if (filled($settings['admin_email']) && ! in_array('email', $settings['channels'], true)) {
            $errors[] = 'Le super administrateur par e-mail demande le canal email.';
        }

        return $errors;
    }

    /**
     * Changer le domaine d'un projet qui a déjà des offres : confirmation, ou --force.
     */
    private function domainChangeAllowed(string $domain): bool
    {
        $current = config('offer.domain');

        if (blank($current) || $current === $domain || ! $this->hasOffers()) {
            return true;
        }

        $message = "Le projet a déjà des offres du domaine « {$current} » : passer à « {$domain} » ?";

        if ($this->option('force') || ($this->input->isInteractive() && confirm($message, default: false))) {
            return true;
        }

        $this->components->error($message.' Relancer avec --force pour confirmer.');

        return false;
    }

    private function hasOffers(): bool
    {
        try {
            return Offer::withTrashed()->exists();
        } catch (Throwable) {
            // Base pas encore migrée : aucune offre.
            return false;
        }
    }

    /**
     * @param  array{domain: string, name: string, locale: string, countries: list<string>, channels: list<string>, admin_email: string|null}  $settings
     * @param  list<string>  $changed
     */
    private function summary(array $settings, array $changed): void
    {
        $this->newLine();
        $this->components->info('Projet initialisé.');
        $this->components->twoColumnDetail('Domaine', $settings['domain']);
        $this->components->twoColumnDetail('Nom', $settings['name']);
        $this->components->twoColumnDetail('Langue par défaut', $settings['locale']);
        $this->components->twoColumnDetail('Pays actifs', implode(', ', $settings['countries']));
        $this->components->twoColumnDetail('Canaux', implode(', ', $settings['channels']));
        $this->components->twoColumnDetail('Catégories', (string) OfferCategory::query()->count());
        $this->components->twoColumnDetail('.env modifié', $changed === [] ? 'rien' : implode(', ', $changed));
        $this->components->twoColumnDetail('Back-office', rtrim((string) config('app.url'), '/').'/admin');
        $this->newLine();
        $this->line('Reste à faire, hors de cette commande : secrets (base, e-mail, stockage), fournisseur SMS, réglages de l\'app mobile.');
    }
}
