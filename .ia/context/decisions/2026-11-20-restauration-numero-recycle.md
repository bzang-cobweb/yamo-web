# `2026-11-20` — Récupérer son compte après la connexion ; numéro d'un compte inactif protégé puis libéré

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-20-restauration-numero-recycle.md`. Comment annuler la suppression ? Comment se protéger d'un numéro recyclé ?
- **Réponse** : « reset si le compte n'a pas été actif depuis n jours (valeur dans la config et le .env) », précisé par « Block SMS reset + free the number ».
  - **Annuler la suppression** (défaut du contrat, la question est restée sans réponse) :
    - se connecter à un compte en attente de suppression mène à « Récupérer mon compte » ;
    - la confirmation restaure le compte et connecte l'appareil (`POST /api/v1/auth/account/restore`).
  - **Compte inactif** : dernière activité (`last_seen_at`, à défaut la création) plus vieille que `config('auth.phone.inactive_days')` (`AUTH_PHONE_INACTIVE_DAYS`). Pour un tel compte :
    - le mot de passe oublié par SMS est refusé, avec une réponse neutre ;
    - le numéro est retiré de l'ancien compte dès qu'un nouveau titulaire le prouve par SMS (inscription ou ajout d'un numéro).
- **Motif** : un opérateur réattribue un numéro abandonné. Son nouveau titulaire ne doit jamais entrer dans l'ancien compte, et doit pouvoir utiliser son numéro.
- **Portée** : module Auth (connexion, mot de passe oublié, inscription, changement de numéro, unicité du téléphone), app mobile (connexion).
- **Corollaires** :
  - un compte resté inactif longtemps et qui n'a qu'un téléphone peut perdre son numéro au profit d'un tiers, puis tout accès ;
  - un compte en attente de suppression garde son numéro jusqu'à la purge.
- **Tranché par** : humain, avec le défaut du contrat pour l'annulation de la suppression
