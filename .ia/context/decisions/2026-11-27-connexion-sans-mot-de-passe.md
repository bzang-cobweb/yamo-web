Remplacée en partie par : 2026-11-28-numero-non-verifie.md (point « numéro jamais vérifié » : il ne se connecte plus par SMS)
Remplacée en partie par : 2026-11-28-connexion-panneau.md, 2026-11-28-app-connexion-panneau.md

# `2026-11-27` — Connexion sans mot de passe, l'inscription à la suite

- **Type** : décision
- **Question posée** : demande du 2026-11-27 (`specs/todo/2026-11-27-yamo-visiteur-profil-favoris.md`, « Visiteur ») :
  plus de mot de passe pour se connecter ; e-mail ou téléphone avec l'indicatif du pays ; compte existant → code ;
  sinon inscription sans les étapes 2 et 3, puis code.
- **Ce qui est retenu** :
  - `auth.login` ne demande que l'identifiant (e-mail, ou pays et numéro ; le pays courant d'office, comme avant).
    `POST /auth/login` : compte trouvé → 202 et le code envoyé ; compte inconnu → 200
    `{next: 'register', values: {email}|{phone, phone_country}}`.
  - `auth.register` : une seule page, pseudonyme, prénom, nom, date de naissance ; l'identifiant y est un champ caché
    que l'app remplit avec celui de la connexion (l'unicité reste vérifiée). Ni mot de passe, ni genre, ni description.
  - `users.password` devient facultatif (migration additive, retour arrière : mot de passe aléatoire aux comptes sans).
    « Mot de passe » du profil : un compte sans mot de passe le crée, sans l'ancien.
  - Un code reçu par SMS à la connexion pose `phone_verified_at` s'il manquait ; un numéro saisi mais jamais vérifié
    (compte créé au back-office) se connecte donc par SMS, au lieu de mener à une inscription refusée.
  - Numéro recyclé : par SMS, un compte inactif (`PhoneOwnership::isInactive`) est traité comme inconnu, comme pour le
    mot de passe oublié (décision 2026-11-20-restauration-numero-recycle) ; sans mot de passe, le code ouvrirait sinon
    le compte de l'ancien titulaire au nouveau. Par e-mail, ce compte se connecte toujours.
  - App : un seul écran, « Se connecter », qui enchaîne l'inscription (titre « Créer un compte », la flèche revient à la
    connexion). Liens « Mot de passe oublié ? » et « Créer un compte » retirés ; écrans `/sign-up` et
    `/forgot-password` retirés de l'app. Les routes d'API du mot de passe oublié restent, sans usage dans l'app.
- **Décidé seul (mode nuit)** :
  - l'inconnu reçoit 200 et non une erreur, pour que l'app enchaîne sans message ;
  - l'identifiant de l'inscription est renvoyé par l'app plutôt que gardé côté serveur : pas d'état entre deux
    requêtes, et le serveur le valide à nouveau ;
  - la réponse dit si un compte existe : c'est voulu par la demande (« on sait si l'utilisateur existe ») ; la route
    reste limitée en fréquence (`throttle:auth-login`).
- **Escalade (défaut appliqué)** : « recevoir le code par WhatsApp ». Il n'existe aucun envoi WhatsApp : il faut un
  fournisseur (Meta Cloud API, Twilio…) et ses identifiants, qui ne se décident pas la nuit. Défaut : le code part
  toujours par SMS (aujourd'hui écrit dans le journal, `LogSmsSender`). À trancher : le fournisseur et le compte.
- **Portée** : module Auth (formulaires, services, migration), app (`SignInScreen`, routes).
- **Tranché par** : session de nuit (interprétations), défaut d'escalade pour WhatsApp
