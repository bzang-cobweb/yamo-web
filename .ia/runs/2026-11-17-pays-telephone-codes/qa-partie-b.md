RAPPORT QA — Partie B (codes), web et mobile
Go : oui
Base du verdict : rejeu moi-même.
- Web : `ddev artisan test --compact` = 163 passed (726 assertions). Pint --test = PASS, 244 fichiers. `migrate:status` : 2026_11_17_120000_create_verification_codes_table = Ran, aucune en attente. `ddev composer openapi` lancé deux fois : aucune erreur, md5 de openapi.json identique avant et après (ba88e55a…), donc aucun diff. openapi.json est modifié dans l'arbre non commité, comme le reste du livrable.
- Mobile : `jest` = 16 suites, 69 tests passés. `tsc --noEmit` = 0 erreur. `eslint src` (binaire local) = aucune sortie. Un premier appel `rtk lint` affichait « 2 errors », mais c'était un artefact de l'enveloppe rtk (npm « could not determine executable »), pas de eslint.
- Lecture du code : VerificationCodeService, Challenges, RegisterService, LoginService, EmailChangeService, PhoneChangeService, VerificationController, Register/EmailChange/PhoneChange requests, formulaires, routes, SmsSender/LogSmsSender, Field::validationRules, RouteGuardTest, noms des tests.
Ne couvre pas : exécution réelle sur appareil ou simulateur mobile ; relecture ligne à ligne des écrans mobiles (SignInScreen, SignUpScreen, ChallengeFlow, écrans de changement) ; aucune capture ni parcours en navigateur ; envoi réel d'e-mail ou de SMS (seul le journal est utilisé) ; concurrence réelle (conditions de course) ; test de charge du throttle.

Critères
1. Codes : OK. 6 chiffres (random_int, pad), 10 min, 5 essais, renvoi après 60 s, hachés (Hash::make ; code_hash et payload masqués ; test ligne 69). SmsSender est une interface liée à LogSmsSender (AuthServiceProvider:38). E-mail traduit selon la locale (test).
2. Inscription : OK. Le compte n'est créé qu'une fois tous les codes validés (RegisterService::complete). Identifiants marqués vérifiés, jeton rendu. required_without e-mail/téléphone, doublons refusés (RegisterRequest::after). Tests présents.
3. Connexion : OK. Mot de passe seul = aucun jeton (test 56). Faux, expiré et épuisé sont couverts. Canal selon l'identifiant ; téléphone au format international et vérifié exigé.
4. Changements : OK. Code envoyé à la nouvelle valeur, donnée inchangée avant validation, défi lié au compte (test 269, VerificationCodeService:68). Les formulaires ont current_* en readonly, le téléphone en type phone avec son pays.
5. Readonly : OK. Field::validationRules renvoie [] pour un champ readonly (Field.php:221).
6. Textes : OK pour ce que j'ai vérifié. Clés ApiMessageKey servies par l'API en fr et en (mobile.php). Seuls OFFLINE_MESSAGES sont locaux. Un grep de chaînes littérales dans src/features/auth et src/app n'a rien trouvé.
7. Mobile : OK structurellement. Un service par appel (Register, Verification, EmailChange, PhoneChange, Login). Écrans sign-up, email, phone et ChallengeFlow présents. Côté Laravel, un contrôleur et un service par formulaire (Register, EmailChange, PhoneChange, Login, Verification).
8. Tests, typage, lint, Pint, migrations, openapi : OK (chiffres ci-dessus).
9. Sécurité : OK. throttle sur login, register, codes/verify, codes/resend, me/email et me/phone. RouteGuardTest : 3 exceptions motivées ajoutées. Le grep du diff ne trouve aucun secret (les seules correspondances sont des `$token = $this->signIn()->json('token')` dans des tests).

Bloquants : 0

Majeurs : 0

Mineurs : 4
- Aucun test ne vérifie le 429 des routes auth-register et auth-codes. Le test de throttle ne couvre que le login (AuthApiTest.php:140). Le contrat demande des routes publiques limitées en fréquence. Le code est correct (api.php:23-31, 41-42) mais non prouvé par un test.
- VerificationCodeService.php:81 : `increment('attempts')` après lecture, sans verrou. Des requêtes parallèles peuvent dépasser légèrement 5 essais ; le throttle de 10 par minute par IP borne le risque.
- Register, EmailChange et PhoneChange : l'unicité est vérifiée à l'ouverture du défi, pas à la validation (EmailChangeService.php:34, PhoneChangeService.php:40, RegisterService::complete). Si un tiers prend la valeur entre-temps, l'échec dépend de la contrainte unique en base (non vérifiée par moi) et peut donner une 500.
- api.php:23 : register envoie un SMS ou un e-mail vers n'importe quelle destination, limité à 5 par minute par IP. Risque d'abus (SMS pumping) à rejeter avec un vrai fournisseur SMS ; sans objet tant que LogSmsSender est le seul branché.
