RAPPORT QA — 2026-11-20-canaux-email-telephone
Go : non (NO-GO)
Base du verdict : rejeu personnel. Web : `ddev artisan test` = 224 passés (1068 assertions) ; pint --test propre (279 fichiers) ; `scramble:export` + diff = identique à openapi.json ; composer.json/lock inchangés. Mobile : jest 26 suites / 95 tests verts ; tsc et expo lint sans sortie d'erreur ; package.json/lock inchangés. Relecture de git diff des deux dépôts, sonde jetable (créée puis supprimée, aucune trace dans git status) : liste des utilisateurs du back-office rendue sans erreur pour (email+phone), (email seul), (phone seul), avec comptes sans téléphone / sans e-mail. Sonde tinker config() : getGloballySearchableAttributes().
Ne couvre pas : rendu visuel de l'app et du back-office, exécution sur appareil, e-mails/SMS réellement envoyés, comportement avec un vrai `.env` (jamais lu), parcours mobile d'inscription/connexion hors profil (textes venus de l'API, vérifiés seulement par les tests API).

Bloquants : 1
- Critère 7 (recherche globale) : `Modules/Auth/app/Filament/Resources/UserResource.php:68` renvoie toujours `['name','first_name','last_name','email']`. Preuve : en tinker, avec `auth.channels.email=true, phone=false` puis avec `email=false` (phone=true), le résultat est identique : `["name","first_name","last_name","email"]`. Avec le téléphone seul, la recherche globale cherche donc encore par e-mail, canal désactivé, alors que la fiche et la colonne le masquent. Le critère cite explicitement la recherche globale. Aucun test ne la couvre. Correction attendue : attributs suivant `AuthChannels` (retirer `email` si le canal est inactif), avec test.

Majeurs : 0

Mineurs : 2
- Avec les deux canaux, la table des utilisateurs gagne une colonne `phone` (masquée par défaut, activable) : le critère 4 (« rien ne change ») est respecté à l'affichage par défaut, mais ce n'est pas strictement identique. À assumer ou à signaler dans la décision.
- Avec les deux canaux à false, l'erreur explicite (LogicException) n'est levée qu'à l'usage (ex. `GET /api/v1/app` ou un formulaire), pas au démarrage. Explicite, mais tardive ; la spec parle de « refuse de démarrer l'authentification ».

Critères vérifiés conformes (preuve : tests rejoués + lecture du diff)
- 1 : config `auth.channels.*`, défaut true, `.env.example` présent, aucun champ back-office, deux false = LogicException (test `test_at_least_one_channel_is_required`).
- 2/3 : RegisterForm (champ unique obligatoire, aide retirée), Login/ForgotPassword (libellé, aide, sous-titre), 403 sur forms/auth.profile.* et POST /me/email|phone (test_a_disabled_channel_cannot_be_added_or_changed), UserForm obligatoire/masqué, auth:create-user `prohibited`.
- 4 : tests existants inchangés verts, ApplicationTest adapté seulement pour `channels`.
- 5 : test_an_existing_account_on_a_disabled_channel_still_signs_in vert.
- 6 : ApplicationResource expose `channels`, openapi identique, ProfileScreen masque ligne et lien (tests jest ajoutés).
- 8 : voir base du verdict.


## Deuxième passage

RAPPORT QA — 2026-11-20-canaux-email-telephone (deuxième passage)
Go : oui (GO)
Base du verdict : rejeu personnel. `ddev artisan test` = 225 passés (1071 assertions), contre 224 au premier passage, soit le test ajouté. `pint --test` propre (279 fichiers). `scramble:export` puis `diff` avec openapi.json : identique. Sonde tinker, rejouée sur `UserResource::getGloballySearchableAttributes()` : (email+phone) donne name, first_name, last_name, email, phone ; (email seul) donne name, first_name, last_name, email ; (phone seul) donne name, first_name, last_name, phone. Le bloquant est levé : l'e-mail n'est plus cherché quand son canal est inactif. Le diff de UserResource.php se limite à cette méthode et à l'import d'`AuthChannels`. `test_the_back_office_global_search_follows_the_channels` couvre les trois cas avec des `assertSame` exacts. `git status` : aucun fichier suivi touché par moi, `.env` non lu.
Ne couvre pas : l'app Expo (inchangée, non rejouée, verdict du premier passage conservé), le rendu visuel, la recherche globale exécutée dans l'interface Filament (seule la liste d'attributs est vérifiée, pas une requête SQL de recherche sur `phone`), un vrai `.env`, l'envoi réel d'e-mails ou de SMS.

Bloquants : 0

Majeurs : 0

Mineurs : 2 (inchangés, non bloquants)
- Colonne `phone` ajoutée à la table des utilisateurs avec les deux canaux (masquée par défaut) : à assumer ou à signaler dans la décision.
- LogicException levée à l'usage et non au démarrage quand les deux canaux sont à false.

Autres critères : 1 à 6 et 8 restent tenus (tests verts, openapi identique, Pint propre). Le critère 7 est désormais conforme.
