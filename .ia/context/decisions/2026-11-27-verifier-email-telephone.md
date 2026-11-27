# `2026-11-27` — Vérifier l'e-mail ou le numéro actuel

- **Type** : décision
- **Question posée** : « envoi du code par mail même connecté, rajouter un lien qui renvoie le code si pas verified » ;
  « envoi du code par téléphone même connecté » ; « ajouter phone_verified_at (on met la date lors d'une connexion par
  téléphone ; rajouter un lien qui renvoie le code si pas verified) ».
- **Ce qui est retenu** :
  - le changement d'e-mail ou de numéro envoie toujours son code à la nouvelle valeur (inchangé) ;
  - valeur actuelle non vérifiée (`email_verified_at` / `phone_verified_at` vide) : le formulaire de changement dit
    « n'est pas encore vérifiée » (aide du champ) et sert un lien (`Form::links()`) « Vérifier mon adresse » vers
    `auth.profile.email.verify`, « Renvoyer le code » vers `auth.profile.phone.verify` ;
  - ces deux formulaires montrent la valeur masquée, en lecture seule, et « Envoyer le code » :
    `POST /auth/me/email/verify`, `POST /auth/me/phone/verify` (202, défi) ; nouvelles `VerificationPurpose`
    `verify_email` / `verify_phone` (compte connecté exigé) ; la validation par `POST /auth/codes/verify` pose la date
    et rend le compte ; rien à vérifier ou déjà vérifié : 422 ;
  - `phone_verified_at` était déjà posé par une connexion par SMS ; une connexion par code e-mail pose de même
    `email_verified_at`.
- **Décidé seul (mode nuit)** :
  - un seul service (`ContactVerificationService`) pour les deux formulaires : même opération, deux canaux ;
  - la vérification par e-mail à la connexion n'était pas demandée : ajoutée par symétrie, sinon un compte qui se
    connecte par e-mail verrait toujours « non vérifiée » ;
  - si la valeur a changé entre l'envoi et la validation, le code est refusé (`code`).
- **Portée** : module Auth (formulaires, services, routes, `LoginService`), app (liens sous le formulaire).
- **Tranché par** : humain (demande), session de nuit pour le détail
