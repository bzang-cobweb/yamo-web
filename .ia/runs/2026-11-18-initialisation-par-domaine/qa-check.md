RAPPORT QA — 2026-11-18-initialisation-par-domaine
Go : oui (avec 1 majeur à corriger avant livraison)
Base du verdict : rejoué moi-même
- `ddev exec php artisan test` : 365 passés, 1825 assertions (dont ProjectInitCommandTest, 7 tests).
- `pint --test` : PASS, 387 fichiers.
- `scramble:export --fail-on-unknown` puis `cmp` avec openapi.json : identique.
- Test jetable (supprimé), .env factices dans storage/app, base SQLite :
  - quoting dans un .env CRLF, avec relecture par Dotenv::parse ;
  - rejeu avec d'autres canaux ;
  - pays multiples ("CH" + "de,fr") ;
  - e-mail admin sans canal email ;
  - nom vide, pays en minuscules, aucune option en non interactif ;
  - mode interactif complet (prompts en repli).
- Lecture du code : commande, EnvFile, seeders, skill.

Ne couvre pas : `project:init --admin-email` jusqu'au bout (non testé : `auth:create-user` demande aussi nom, prénom, mot de passe). Activation réelle d'un module requis (`modules()` est vide pour tous les domaines : `Module::enable` non exercé, et il réécrit le fichier suivi modules_statuses.json). Production réelle. Domaine boutique et rencontre. Exécution contre la base DDEV ou le .env réel (interdit). Le test de production est simulé par `app['env']`.

Bloquants : 0

Majeurs : 1
- EnvFile::quote n'échappe pas `${...}`. Un nom `${APP_KEY}x` est écrit `APP_NAME="${APP_KEY}x"` ; Dotenv le relit `base64:zzx`. Le secret est donc interpolé dans APP_NAME, ce qui contredit « aucune lecture de secret ». Reproduit. `$HOME` et `$B` (sans accolades) sont relus correctement. Correction : refuser ou échapper `${`, par exemple en simple guillemet quand il n'y a pas d'apostrophe. Ajouter un test.

Mineurs : 4
- Un .env en CRLF est réécrit en LF (`preg_split('/\R/')` puis `implode("\n")`) : « octet pour octet » est faux pour ces fichiers. Sans CRLF, les autres lignes sont préservées, y compris les espaces de fin, la ligne vide finale et un fichier sans fin de ligne. Seul l'écrasement `.env` est concerné par le CRLF (peu probable).
- Les clés dupliquées (y compris `export APP_NAME=`) : un `export APP_NAME=old` n'est pas reconnu, la clé est ajoutée en doublon. Les lignes `#APP_NAME` et `APP_NAME_X` sont correctement ignorées.
- Le chemin `--admin-email` et le chemin `modules()` non vides n'ont aucun test (critère 3 ne les exige pas). L'admin est créé après l'écriture de .env et le seed : un échec laisse l'initialisation faite, mais elle est rejouable.
- Le skill dit « Offres existantes : le signaler » mais n'indique pas comment les compter sans lire la base ; sinon conforme (voir ci-dessous).

Vérifications conformes
- Tout est validé avant écriture : domaine, nom, langue, pays, canaux, e-mail. Pas d'effet partiel, `.env` inchangé et 0 pays en base après les échecs.
- EnvFile refuse toute clé hors WRITABLE (testé). Seules les 5 clés sont écrites ; APP_KEY et DB_PASSWORD intacts.
- Un nom avec espaces, guillemets, `#`, `=`, `\` est relu à l'identique.
- Rejeu : « .env modifié : rien », fichier identique. Un rejeu avec d'autres canaux met à jour proprement.
- Production sans --force : refus, rien d'écrit. Changement de domaine avec offres : refus sans --force (offres supprimées comprises, `withTrashed`) ; avec --force, accepté. Confirmation en interactif.
- Mode interactif : sélection domaine, nom, langue, pays, canaux, confirmation admin ; .env écrit et pays actifs corrects.
- Critère 1 : menu « Mes logements », champ `surface`, 3 catégories, seul CH actif (test présent et passé).
- Skill : n'écrit jamais .env (lit les clés via l'outil env), n'utilise pas `--admin-email` ni mot de passe, renvoie à `bza:lead` pour un domaine nouveau, commandes exactes (`project:init --list`, options correctes).

Risques signalés (non bloquants)
- `db:seed` complet rejoué à chaque lancement : Auth ajoute les permissions de base aux rôles et SUPPRIME les permissions hors PermissionEnum ; CountrySeeder réécrit les noms des pays (garde `active` et `dial_code`) ; les catégories existantes ne sont ni modifiées ni déplacées. Comportement documenté dans les seeders, mais le résumé n'en avertit pas.
- Les pays absents de --country sont désactivés, même ceux déjà utilisés par des comptes ou des adresses : aucun avertissement (le contrat dit « désactive les autres »).
- Changer de domaine garde les catégories de l'ancien domaine ; seul le domaine actif change.
- `migrate --force` est toujours lancé, y compris hors production : conforme au contrat.
- Dans mes essais, un fichier .env factice créé par moi dans storage/app/qa-env2/ a été supprimé par le test lui-même. Un hook a refusé une suppression manuelle ; il n'en reste rien. storage/app est propre, git status vide.

## Revérification (commit 5ab03de)
Rejoué : test jetable (supprimé) sur .env factice CRLF, relecture par Dotenv::parse ; suite web ; Pint ; OpenAPI.
- Majeur `${...}` : corrigé. `${APP_KEY}x`, `${APP_KEY}`, `$`, `$HOME`, `A $B c` et `Mon "Y" #1 $HOME` sont écrits entre apostrophes et relus à l'identique ; plus aucune interpolation. Les autres lignes (secrets compris) restent intactes.
- Valeur avec `$` et apostrophe (`a $x's`) : refusée par InvalidArgumentException, fichier inchangé, aucun pays créé. `it's` sans `$` : écrit entre guillemets doubles, relu à l'identique.
- Mineur CRLF : corrigé. Un fichier en CRLF reste en CRLF et les autres lignes sont préservées.
- Mineur `export` : corrigé. `export APP_NAME=old` est remplacé, sans doublon ; `APP_NAME_X` et `#APP_NAME` sont ignorés. Un rejeu à valeur identique ne change rien.
- Suite web : 367 passés, 1829 assertions. Pint : PASS, 387 fichiers. OpenAPI : identique à openapi.json (comparaison faite après un 2e export : le premier cmp avait précédé l'apparition du fichier sur l'hôte, sans lien avec le code).
- Nouveau mineur : le refus d'une valeur avec `$` et apostrophe sort en exception brute (trace) au lieu d'un message d'erreur propre, car il n'est pas vérifié dans `errors()`. Rien n'est écrit avant, donc pas d'effet partiel.
- Non rejoué : les deux mineurs « admin et module non testés » restent ouverts (inchangés).

**Verdict final : GO.** 0 bloquant, 0 majeur ; mineurs restants : message de refus brut, `--admin-email` et `modules()` non testés, calcul d'offres du skill.
