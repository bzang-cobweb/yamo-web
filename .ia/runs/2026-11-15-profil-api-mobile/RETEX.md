# RETEX — chantier 2026-11-15-profil-api-mobile

## Ce qui s'est produit deux fois → règles candidates

1. **Le garde-fou des secrets refuse toute commande dont le texte contient `.env`**, même pour
   un `git check-ignore .env` ou un `cp .env.example .env` (3 refus). Règle candidate pour le
   projet : écrire `.env` / `.env.example` uniquement par `bza:env` (`set`, `init`), éditer
   `.gitignore` avec l'outil d'édition, et ne jamais nommer `.env` dans une commande composée.
2. **Le garde-fou prend un mot `id` isolé pour la commande système `id`** (`orderBy('id')`,
   `echo $u->id` dans un tinker) : 2 refus. Règle candidate (marketplace, `bash-guard`) :
   ne viser que `id` en position de commande ; en attendant, écrire `getKey()` / `oldest()`.
3. **Outils qui lisent la base hors DDEV** (`php artisan migrate:status`, `scramble:export`) :
   échec « hôte db introuvable » sur l'hôte. Règle candidate pour le projet (déjà notée dans
   `project.md`) : tout ce qui lit la base passe par `ddev artisan` / `ddev composer`.

## Autres leçons

- **Le navigateur intégré lit la configuration de lancement du dossier d'origine de la session**
  (anchette) : il a démarré le serveur d'anchette. Arrêté aussitôt ; Expo lancé à la main. À
  signaler : ouvrir une session directement dans `yamo/web` ou `yamo/mobile` évite le problème.
- **Tests de tri avec Faker `fr_FR`** : les noms se répètent, l'ordre des ex æquo est
  indéterminé → test instable. Données de tri explicites et distinctes.
- **Le client de test Symfony envoie `Accept-Language: en-us`** par défaut : un test de langue
  demande chaque langue explicitement.
- **Changement de décision en cours de chantier** (`name` pseudonyme) : traité par une entrée qui
  remplace, une nouvelle migration (jamais la modification d'une migration exécutée) et un avenant
  au contrat — sans réécrire l'historique.

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliée** : la liste des langues (`App\Support\Locales`) — un seul endroit pour le
  formulaire, la table, la commande, le middleware.
- **Domiciliée** : l'autorisation ligne par ligne de la suppression groupée, dans la base commune
  `AbstractResource`, pour toutes les ressources.
- **Ré-implémentée, à surveiller** : les règles de validation du compte existent dans le formulaire
  Filament et dans `auth:create-user` (pas de FormRequest commune possible côté Filament) ; à
  comparer quand l'API de modification du profil arrivera.
