# Initialisation d'un projet selon son domaine : commande `project:init` et skill

Demande du 2026-11-18 (suite de `2026-11-18-offres-et-domaines`) : « on puisse définir une skill qui initialise le projet selon le bon domaine », avec « juste quelques configurations, un projet déjà opérationnel ». Validée pour un contrat : « yes ».

## Ce que j'ai compris

Deux pièces :

- **la commande** fait le travail, de façon testable et rejouable ;
- **le skill** guide la personne, lance la commande et vérifie le résultat. Toute la logique vit dans la commande.

### Commande `php artisan project:init`

**Options** (chacune demandée interactivement si elle manque) :

- `--domain=rental` : un domaine inscrit dans `DomainRegistry`, choisi dans la liste des domaines connus ;
- `--name="Yamo"` : nom de l'application (`APP_NAME`) ;
- `--locale=fr` : langue par défaut (`APP_LOCALE`), parmi les langues de l'application ;
- `--country=CH` : pays actifs (un ou plusieurs codes ISO). Un seul pays verrouille le choix du pays, comme aujourd'hui (décision 2026-11-20-pays-unique-identifiant-double) ;
- `--channels=email,phone` : canaux d'inscription et de connexion (`AUTH_CHANNEL_EMAIL`, `AUTH_CHANNEL_PHONE`) ;
- `--admin-email=…` : facultatif. Crée le premier super administrateur, en réutilisant `auth:create-user`, dont le mot de passe est demandé de façon masquée. Sans cette option, rien n'est créé.

**Ce qu'elle fait, dans l'ordre :**

1. Elle vérifie tout avant de rien écrire : domaine connu, langue et pays valides. Une erreur arrête tout, sans effet partiel.
2. Elle écrit dans `.env` les **réglages non secrets** : `APP_NAME`, `APP_LOCALE`, `OFFER_DOMAIN`, `AUTH_CHANNEL_*`.
   - Une variable absente est ajoutée.
   - **Aucun secret n'est écrit ni lu.**
3. Elle active les modules requis par le domaine (`modules_statuses.json`). Aujourd'hui, tous les domaines n'utilisent que les modules existants. Le mécanisme est prêt pour Booking, Order…
4. Elle lance les migrations et les seeders : rôles et permissions, pays, catégories du domaine.
5. Elle active les pays choisis et désactive les autres.
6. Elle affiche un **résumé** : domaine, langue, pays, canaux, nombre de catégories, URL du back-office, et ce qui reste à faire (secrets, fournisseur SMS, e-mail).

**Comportement :**

- **Rejouable :** relancée avec les mêmes options, elle ne change rien. Avec un autre domaine, elle avertit si des offres existent déjà, et demande confirmation (`--force` en mode non interactif).
- **Production :** elle refuse de tourner sans `--force`, comme `migrate`.

### Skill `projet-domaine`

Il est rangé dans `.ia/skills/projet-domaine/SKILL.md`, dans le dépôt web.

1. **Il lit l'état :**
   - les variables présentes, sans leurs valeurs, avec l'outil `env` ;
   - les domaines connus, avec `project:init --list`.
2. **Il pose les questions au format d'escalade :** domaine, nom, langue, pays, canaux, premier administrateur.
   - Pour un domaine qui n'existe pas encore (boutique, rencontre), il **ne l'invente pas**. Il propose d'ouvrir le chantier de ce domaine avec `bza:lead` : classe `Domain`, attributs, catégories, textes, tests.
3. **Il lance la commande,** puis vérifie :
   - les tests ;
   - `GET /api/v1/auth/me/menu`, qui doit contenir le lien du domaine ;
   - le formulaire `offer.offer`, qui doit contenir les attributs du domaine.
4. **Il consigne le choix** dans le registre des décisions du projet (`bza:decision`).

## Critères de succès

1. `project:init --domain=rental --name=Test --locale=fr --country=CH --channels=email,phone --no-interaction` sur une base vide donne un projet opérationnel :
   - le menu contient « Mes logements » ;
   - le formulaire contient les attributs du domaine ;
   - les catégories sont créées, et seul CH est actif.
2. Relancée à l'identique, elle ne change rien. Un domaine, une langue ou un pays inconnu l'arrête sans rien écrire.
3. Elle n'écrit ni ne lit aucun secret de `.env`. Un test le vérifie sur un `.env` de test : seules les clés prévues changent.
4. Le skill existe, décrit sa marche à suivre, et renvoie à `bza:lead` pour un domaine nouveau.
5. Les tests web passent, Pint est propre, et `openapi.json` est inchangé.

## À trancher

- **Écrire dans `.env` depuis la commande.** C'est l'usage Laravel (`key:generate` le fait). Mais les règles du projet font passer toute écriture du `.env` par l'outil `env` quand c'est Claude qui agit.
  - **Recommandé :** la commande écrit elle-même, et seulement les clés non secrètes de la liste ci-dessus. Le skill ne touche jamais `.env` directement.
  - **Autre option :** la commande n'écrit rien dans `.env`. Elle affiche les lignes à poser, et le skill les pose avec l'outil `env`.
  - **Sans réponse,** j'applique la recommandation.

## Hors périmètre

- Les domaines boutique et rencontre. Chacun aura son chantier.
- Le paramétrage de l'app mobile : nom, icône, couleurs.
- Les secrets, l'envoi d'e-mails et le fournisseur SMS. Le résumé les signale.

## Ce que j'ai décidé seul

- **L'écriture du `.env` passe par `App\Support\EnvFile`**, qui refuse toute clé hors des cinq prévues et ne réécrit les autres lignes que telles quelles. Les fins de ligne (CRLF ou LF) sont conservées, et une ligne `export CLÉ=` est reconnue.
- **Une valeur qui contient `$` est écrite entre apostrophes** : Dotenv ne peut alors y remplacer aucune variable. Le défaut avait été trouvé par la QA : `${APP_KEY}` recopiait un secret. Un `$` avec une apostrophe ou un retour à la ligne est refusé, avec un message.
- **Pays :** codes vérifiés contre la liste de libphonenumber, avant même que la table soit remplie. Les pays non listés sont désactivés.
- **Changement de domaine :** il exige `--force` dès que des offres existent, corbeille comprise.
- **`Domain::modules()`** déclare les modules requis par un domaine. Il est vide pour `rental`.
- **`--list`** affiche les domaines connus. `DomainRegistry` gagne `keys()` et `make()`.
- **Le skill ne passe jamais `--admin-email`** : le mot de passe ne peut être saisi que par l'humain, qui lance lui-même `auth:create-user --super-admin`.

## Ce qui a été fait

- Commits :
  - `919e009` : commande, `EnvFile`, tests, skill `.ia/skills/projet-domaine/SKILL.md` ;
  - `5ab03de` : correctif du `$`, CRLF et `export` ;
  - dernier commit : refus propre d'un nom avec `$` et une apostrophe, et clôture.
- Tests de la commande (9), sur un `.env` de test qui contient de faux secrets :
  - projet opérationnel ;
  - relance sans aucun changement ;
  - erreur avant toute écriture ;
  - production ;
  - changement de domaine ;
  - écrivain du `.env`.
- Contrôles : 367 tests web passent, Pint est propre et `openapi.json` est inchangé.
- QA (`bza-dev:qa-check`) :
  - premier verdict : GO, avec un écart majeur sur le `$` ;
  - correctif ;
  - revérification : GO (`.ia/runs/2026-11-18-initialisation-par-domaine/qa-check.md`).

## Ce que je n'ai pas vérifié

- **La commande n'a pas été lancée sur la base locale ni sur ton vrai `.env`.** Elle aurait modifié ton nom d'application et désactivé tous les pays sauf ceux listés.
- **`--admin-email` jusqu'au bout**, parce que `auth:create-user` demande un mot de passe.
- **L'activation réelle d'un module**, parce qu'aucun domaine n'en requiert encore.
- **La production réelle.**

## Ce qui reste en suspens

- **À chaque lancement, `db:seed` complet est rejoué.** Le seeder Auth retire les permissions qui ne sont plus déclarées, et `CountrySeeder` réécrit les noms des pays.
- **Un changement de domaine garde les catégories de l'ancien domaine.**
- **Domaines suivants :** boutique et rencontre, chacun dans son chantier.
- **Rien n'est poussé.**
