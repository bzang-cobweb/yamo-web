# Annuler la suppression de son compte ; numéro de téléphone recyclé

Demande du 2026-11-20.

## Ce que j'ai compris

### 1. Annuler la suppression de son compte

Pendant le délai avant la purge (`AUTH_ACCOUNT_DELETION_GRACE_DAYS`), le titulaire peut récupérer son compte lui-même, sans passer par un administrateur.

Parcours prévu (question 1) :
1. Il se connecte comme d'habitude, avec son identifiant, son mot de passe et le code reçu.
2. Le compte étant en attente de suppression, la validation du code ne le connecte pas. Elle répond `{next: "restore", challenge, deletion_date}`, sur le modèle de `next: "password"` du mot de passe oublié.
3. L'app affiche « Votre compte sera supprimé définitivement le …. Voulez-vous le récupérer ? », avec deux boutons : « Récupérer mon compte » et « Annuler ».
4. « Récupérer mon compte » appelle `POST /api/v1/auth/account/restore` (`AccountController::restore`) avec le défi validé. Le compte est restauré, l'app est connectée et affiche une confirmation.
5. Un compte déjà purgé est introuvable, comme aujourd'hui.

Le mot de passe oublié marche aussi pour un compte en attente de suppression : il aboutit au même choix.

### 2. Numéro de téléphone recyclé

Un opérateur réattribue un jour un numéro abandonné. Le nouveau titulaire du numéro ne doit jamais entrer dans le compte de l'ancien.

- **Connexion** : déjà protégée. Il faut le mot de passe, en plus du code reçu par SMS.
- **Mot de passe oublié par SMS** : c'est la faille. Le code SMS suffit aujourd'hui pour changer le mot de passe. Voir la question 2.
- **Effet secondaire** : le nouveau titulaire ne peut pas non plus s'inscrire avec son propre numéro, puisqu'il est encore attaché à l'ancien compte. Voir la question 3.

## Réponses à la validation

1. **Annuler la suppression** : confirmation après la connexion, le parcours décrit ci-dessus. C'est le défaut du contrat, la question est restée sans réponse.
2. et 3. « reset si le compte n'a pas été actif depuis n jours (valeur dans la config et le .env) », précisé par « Block SMS reset + free the number ». Un compte est **inactif** quand sa dernière activité (`last_seen_at`, à défaut sa création) date de plus de `config('auth.phone.inactive_days')` jours (`AUTH_PHONE_INACTIVE_DAYS`). Alors :
   - **son mot de passe ne peut plus être réinitialisé par SMS**. La réponse reste neutre, comme pour un compte inconnu, et rien n'est envoyé ; l'e-mail reste possible s'il en a un ;
   - **son numéro est libéré au profit d'un nouveau titulaire** qui le prouve par un code SMS, à l'inscription ou en ajoutant un numéro à son compte. À ce moment-là, le numéro est retiré de l'ancien compte.
   - Un compte en attente de suppression garde son numéro jusqu'à la purge.

## Critères de succès

- Un compte en attente de suppression peut être récupéré par son titulaire, selon la réponse à la question 1. Après la purge, il ne peut plus l'être.
- Le nouveau titulaire d'un numéro ne peut pas changer le mot de passe du compte de l'ancien titulaire avec le seul code SMS.
- Le cas de l'inscription avec un numéro recyclé suit la réponse à la question 3.
- Le délai d'inactivité est réglable dans la configuration et le `.env`, comme le délai de suppression.
- Tests web et mobile verts, OpenAPI régénéré, aucune dépendance ajoutée.
