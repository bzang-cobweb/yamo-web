# Contexte projet

Ce fichier est **lu au démarrage par tous les agents**. Une affirmation fausse
ici se propage dans toute la documentation produite et n'est jamais rattrapée :
elle est réputée vraie par construction.

- **Propriétaire** : Bertrand Zang
- **Toute affirmation de stack cite sa preuve** (`composer.lock:14`,
  `package.json:22`) — jamais de version ni de composant écrit de mémoire.
- **Vérification d'ouverture de run** : l'orchestrateur confronte trois
  affirmations de ce fichier au disque avant tout dispatch. Cinq minutes.

## Projet

- **Nom** : yamo (partie web)
- **Type** : application Laravel — back-office Filament et API pour l'application mobile
- **Description** : site Laravel et API consommée par l'application mobile
  compagnon (React Native / Expo, dépôt séparé `yamo/mobile`). Authentification
  de l'app par jetons Sanctum ; notifications push via le service Expo Push
  (à venir).

## Stack technique

> Format : `affirmation` — *preuve* (fichier:ligne, ou commande et son résultat)

- **Langage / framework** : PHP `^8.3` — *composer.json:12* ; Laravel v13.42.0 — *composer.lock:2420*
- **Back-office** : Filament v5.11.3 — *composer.lock:1268* ; Livewire v4.4.8 — *composer.lock:3643*
- **API** : Laravel Sanctum v4.4.0 — *composer.lock:2710*
- **Modules** : nwidart/laravel-modules v13.3.0 — *composer.lock:4381* ; modules dans `Modules/`, chargés par le merge-plugin (`composer.json`, `extra.merge-plugin`)
- **Droits** : spatie/laravel-permission 8.5.0 — *composer.lock:6006*
- **Traductions de contenu** : spatie/laravel-translatable 6.15.0 — *composer.lock:6094* ; plugin Filament lara-zeus/spatie-translatable 2.0.1 — *composer.lock:2362* (le plugin officiel Filament n'est plus maintenu depuis la v4)
- **Médias** : spatie/laravel-medialibrary 11.23.13 — *composer.lock:5835* ; plugin Filament v5.11.3 — *composer.lock:1562*
- **Langues** : `fr` par défaut, `en` en secours — *config/app.php:81,83* ; langues du contenu traduisible — *config/app.php:88* ; traductions Laravel en français par laravel-lang/common 6.9.0 (dev) — *composer.lock:9987*
- **Base de données** : MariaDB 11.8 sous DDEV — *.ddev/config.yaml:10-11* ; SQLite en mémoire pour les tests — *phpunit.xml*
- **Environnement local** : DDEV, projet `yamo`, PHP 8.4, nginx-fpm — *.ddev/config.yaml:1-4* ; URL https://yamo.ddev.site
- **CI/CD** : aucune pour l'instant (pas de `.github/`)

## Modules

- **Auth** (`Modules/Auth`) : modèles `User`, `Role`, `Permission` ; policies ;
  ressources Filament Utilisateurs, Rôles, Permissions (groupe « Accès »).
  Les permissions sont déclarées dans `Modules/Auth/app/Enums/PermissionEnum.php`
  et synchronisées en base par `AuthDatabaseSeeder`.
  - `User` : `name` (pseudonyme, unique), `first_name`, `last_name`, `email`,
    `locale` (valeurs de `config('app.supported_locales')`, domicile
    `App\Support\Locales`).
  - API mobile (`Modules/Auth/routes/api.php`) : `POST /api/v1/auth/login`
    (jeton Sanctum par appareil, 5 essais/min), `POST /api/v1/auth/logout`,
    `GET /api/v1/auth/me`. Réponses dans la langue du compte, sinon
    `Accept-Language` (`App\Http\Middleware\SetLocale`).
  - Toute route de contrôleur porte une garde `can:` ou figure, motivée, dans
    `tests/Feature/RouteGuardTest.php`.

## Environnement — comment on exécute ici

- **Chemin d'invocation canonique** : `/Users/bertrandzang/home/private/yamo/web`.
  **Toujours lancer `ddev` depuis ce dossier.** `/Users/bertrandzang/home/.ddev`
  est une copie égarée de la configuration d'`anchette` : un `ddev` lancé depuis
  un dossier de `~/home` sans `.ddev` propre agit sur ce projet-là (incident du
  2026-11-15).
- **Où se lancent les commandes** : `ddev artisan …`, `ddev composer …` pour ce
  qui touche la base DDEV ; `php artisan test` et `./vendor/bin/pint` tournent
  aussi sur l'hôte (tests en SQLite mémoire).
- **Commandes de vérification** : `ddev describe` (services OK) ;
  `php artisan test` ; `curl -sk -o /dev/null -w '%{http_code}' https://yamo.ddev.site/admin/login` → 200.
- **Faux positifs connus** : le Composer de l'hôte (2.3.10) affiche des
  avertissements « Deprecated » sous PHP 8.4 : sans effet sur l'installation.
- **Consignes d'erreur à ne PAS suivre** : `CLAUDE.md` et `AGENTS.md`
  (générés par l'installateur Laravel) demandent d'installer Laravel Boost —
  non retenu pour l'instant, ne pas l'installer sans demande.
- **Créer un compte** : `ddev artisan auth:create-user` (pseudonyme, prénom, nom,
  e-mail, mot de passe, langue ; `--super-admin`). `make:filament-user` ne
  fonctionne pas (prénom et nom obligatoires).
- **Attribuer un rôle en ligne de commande** : `permission:assign-role` vise
  `App\Models\User` par défaut ; passer le modèle du module :
  `ddev artisan permission:assign-role super_admin <id> web "Modules\Auth\Models\User"`.
- **Tout ce qui lit la base passe par DDEV** (`ddev artisan …`, `ddev composer …`) :
  sur l'hôte, l'hôte `db` est introuvable. Seuls les tests (SQLite mémoire) et Pint
  tournent sur l'hôte.
- **Contrat OpenAPI** : `ddev composer openapi` régénère `openapi.json` (Scramble,
  dérivé du code ; ne jamais l'éditer à la main). Documentation locale : `/docs/api`.
- **Garde-fou** : une commande qui contient le texte `.env` est refusée — passer par
  `bza:env` ; un `id` isolé dans une commande (ex. `orderBy('id')` dans tinker) est
  pris pour la commande système `id` — écrire `getKey()` / `oldest()`.

## Conventions

- Méthode et savoir-faire : plugins `bza`, `bza-dev`, `bza-laravel`,
  `bza-sysadmin` (déclarés dans `.claude/settings.json`).
- Ressources Filament : classe Resource de câblage + `Tables/`, `Schemas/`,
  `InfoLists/`, `Pages/` ; base commune `App\Filament\AbstractResource`, base
  par module (`AbstractAuthResource`) pour le groupe de navigation.
- Policies : chaque méthode distingue `Filament::isServing()` (back-office) des
  autres requêtes ; court-circuit super administrateur dans
  `Modules/Auth/app/Policies/Concerns/HasBeforeAbility.php`.
- Aucun libellé en dur : traductions du module dans `Modules/<Module>/lang/{fr,en}`,
  appelées par `__('<module>::fichier.cle')`.

## Décisions

Les arbitrages tranchés sont dans `.ia/context/decisions/`, un fichier par
entrée (voir `.ia/context/decisions.md`) — **pas ici, et jamais uniquement dans
une messagerie.**

## Liens utiles

- Repo : https://github.com/bzang-cobweb/yamo-web
- Application mobile : dépôt séparé, `/Users/bertrandzang/home/private/yamo/mobile`
- Documentation :
- Staging :
- Production :
