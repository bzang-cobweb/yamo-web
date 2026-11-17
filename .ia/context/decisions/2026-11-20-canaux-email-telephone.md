Remplacée par : 2026-11-20-identifiant-selon-canal.md

# `2026-11-20` — Canaux de compte e-mail et téléphone réglés par la configuration

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-20-canaux-email-telephone.md`. Où se règle le choix ? Que faire des comptes qui n'ont que le canal désactivé ?
- **Réponse** : « Pour finir ne pas gérer de nouveau champ dans le backoffice, les définir dans la config est suffisant ».
  - **Réglage** :
    - `config('auth.channels.email|phone')`, alimentés par `AUTH_CHANNEL_EMAIL` et `AUTH_CHANNEL_PHONE` ;
    - `true` par défaut ;
    - au moins un canal doit rester activé.
  - **Partout** : inscription, connexion, mot de passe oublié, profil, codes, fiche utilisateur du back-office, commande de création et `GET /api/v1/app` suivent ce réglage.
  - **Comptes existants** (défaut appliqué, la question est restée sans réponse) :
    - ils se connectent toujours avec leur identifiant ;
    - le canal désactivé ne peut plus être ni inscrit, ni ajouté, ni changé.
- **Motif** : déployer l'application avec un seul canal (par exemple sans SMS) sans toucher au code.
- **Portée** : module Auth, back-office (fiche utilisateur), API, app mobile.
- **Corollaires** :
  - changer le réglage demande de relancer l'application (cache de configuration) ;
  - passer à un seul canal laisse les comptes existants intacts.
- **Tranché par** : humain, avec le défaut du contrat pour les comptes existants
