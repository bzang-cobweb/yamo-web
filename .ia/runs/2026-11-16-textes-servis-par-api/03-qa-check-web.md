RAPPORT QA — 2026-11-16-textes-servis-par-api, étape 1 (web), tâche 03
Go : oui
Base du verdict : rejoué moi-même
- `ddev exec php artisan test` : 90 passés, 313 assertions, 0 échec. `pint --test` : passed.
- RouteGuardTest : exception motivée présente pour api/v1/messages (diff relu), tests au vert.
- openapi.json : `scramble:export --path=/tmp/x.json` puis diff avec openapi.json : identique.
- GET /api/v1/messages sur yamo.ddev.site : public, fr et en, 24 clés aplaties (avatar.*, form.*, home.*, profile.*, error.unknown), aucun error.network ; lang/fr/mobile.php et lang/en/mobile.php ont le même nombre de clés (+ test d'égalité des clés) ; sources : lang/mobile.php de l'application + auth::mobile + form::mobile. Compte en en + Accept-Language fr : réponse en en (la langue du compte l'emporte).
- GET /api/v1/forms/auth.login : fr « Se connecter » / « Accédez à votre compte yamo. », en « Sign in » / « Access your yamo account. ». auth.profile.locale : title et subtitle null, help du champ locale « Language of the app and of e-mails. ».
- 401 : jeton absent ou invalide, fr « Vous devez vous connecter. », en « You must sign in. ».
- Policy : route jetable (en mémoire, rien d'écrit) avec Response::deny("Mon message propre") : message conservé en fr et en. Un refus par défaut est traduit (« Vous n'avez pas le droit… » / « You are not allowed… »).
- Médias : envoi réel d'avatar par l'API avec un compte jetable (qa-tmp@example.test) : fichiers sous storage/app/public/auth/avatar/<uuid>/avatar.webp et .../conversions/avatar-thumb.webp, et l'URL publique correspondante. Compte supprimé par `$user->delete()` : le disque est revenu à .gitignore seul, 0 compte @example.test restant. Fichiers temporaires dans le scratchpad uniquement.
- Aucun fichier du dépôt modifié par la QA (hors ce rapport).
Ne couvre pas : l'envoi réel par Filament (couvert seulement par UserAvatarTest, au vert : chemin auth/avatar/<uuid>) ; le chemin app/<champ>/ pour un modèle hors module (couvert seulement par le test unitaire ModuleFieldPathGeneratorTest) ; le côté app mobile ; la migration des anciens fichiers déjà stockés sous l'ancien rangement ; les 401 sur un jeton révoqué en réel (test automatisé seulement).

Bloquants : 0

Majeurs : 0

Mineurs : 1
- Aucun test automatisé ne vérifie qu'un message propre de policy n'est pas écrasé (je l'ai vérifié à la main, comportement correct) ; un test de non-régression serait souhaitable.
