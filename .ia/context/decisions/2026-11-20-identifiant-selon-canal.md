Remplace : 2026-11-20-canaux-email-telephone.md

# `2026-11-20` — L'identifiant de connexion suit le canal unique : champ e-mail ou champ téléphone

- **Type** : décision
- **Question posée** : avec un seul canal, passer le champ de connexion en champ e-mail (clavier e-mail) ?
- **Réponse** : « yes, do it. faire pareil si c'est seulement le numéro de téléphone en affichant les champs pays et téléphone sur la même ligne ».
  - **Les deux canaux** : un texte, e-mail ou téléphone au format international (inchangé).
  - **E-mail seul** : un champ e-mail, avec le clavier e-mail. La saisie doit être une adresse.
  - **Téléphone seul** : un champ téléphone, le pays et le numéro sur une ligne. Le serveur en tire l'E.164.
  - Même règle pour le mot de passe oublié.
  - Le reste de l'entrée remplacée tient toujours :
    - les canaux sont réglés par la configuration (`AUTH_CHANNEL_EMAIL`, `AUTH_CHANNEL_PHONE`), et au moins un doit rester activé ;
    - inscription, profil, back-office, commande et `GET /api/v1/app` suivent ce réglage.
- **Motif** : saisir l'identifiant avec le bon clavier, et le numéro comme partout ailleurs (pays puis numéro).
- **Portée** : formulaires `auth.login` et `auth.password.forgot`, résolution de l'identifiant (`Identifiers`).
- **Corollaires** :
  - **Change le défaut de l'entrée remplacée.** Avec un seul canal, un compte qui n'a que l'autre canal ne peut plus se connecter depuis l'app, puisque son identifiant ne rentre plus dans le champ.
  - Ses données restent intactes. Réactiver le canal, ou ajouter l'autre canal au compte depuis le back-office, lui rend l'accès.
- **Tranché par** : humain
