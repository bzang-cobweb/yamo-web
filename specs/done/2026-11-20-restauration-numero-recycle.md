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

## Ce que j'ai décidé seul

- **Délai d'inactivité** : 180 jours par défaut (`AUTH_PHONE_INACTIVE_DAYS`). Les opérateurs réattribuent un numéro après quelques mois sans usage ; 180 jours laissent de la marge. L'activité est `last_seen_at`, mis à jour à chaque appel connecté, ou la date de création pour un compte jamais vu.
- **Mot de passe oublié par SMS refusé** : la réponse reste neutre, comme pour un compte inconnu. L'utilisateur ne sait pas pourquoi aucun SMS n'arrive ; c'est le prix de ne pas révéler l'existence du compte.
- **Mot de passe oublié sur un compte en attente de suppression** : il fonctionne. Le titulaire se connecte ensuite et voit la proposition de récupérer son compte.
- **« Annuler » sur l'écran de récupération** : retour à l'écran d'accueil. Le compte reste en attente de suppression.
- **Course entre deux demandes** : la fin des défis qui enregistrent un identifiant (inscription, changement d'e-mail, changement de numéro) passe par `Challenges::persist`, une transaction qui transforme une collision en refus propre (422). Ce correctif fait suite au NO-GO du premier qa-check.
- **Numéro libéré** : le compte inactif perd son numéro ; son titulaire en est prévenu par e-mail, s'il en a un (ajouté à la demande de l'humain après la clôture : `PhoneReleasedMail`, dans la langue du compte, numéro masqué, envoyé seulement une fois l'attribution enregistrée).

## Ce qui a été fait

- API :
  - connexion d'un compte en attente de suppression : `{next: "restore", challenge, deletion_date}` ;
  - `POST /api/v1/auth/account/restore` (`AccountController::restore`, `AccountDeletionService::restore`) ;
  - `PhoneOwnership` (inactivité, numéro tenu, libération), utilisé par `UniquePhone`, `ensureFree`, l'inscription et le changement de numéro ;
  - mot de passe oublié par SMS neutralisé pour un compte inactif ;
  - `Challenges::persist` ;
  - config `auth.phone.inactive_days` et `.env` ;
  - OpenAPI régénéré.
- App : résultat `restore` du code, `AccountRestoreService`, écran `RestoreAccountPrompt` sur la connexion (date, « Récupérer mon compte », « Annuler »).
- Tests :
  - web : 244 (5 de récupération, 10 sur les numéros recyclés, dont la course) ;
  - app : 102 (`RestoreAccountPrompt`, 4).
- qa-check : NO-GO au premier passage (la course), puis GO.

## Ce que je n'ai pas vérifié

- La récupération à l'écran : le simulateur est connecté au compte de l'humain, que je n'ai pas déconnecté. Elle est couverte par les tests de l'API (connexion, code, récupération, `/me`) et de l'app.
- Une vraie course entre deux processus : elle n'est que simulée dans un test.
- L'envoi réel d'un SMS (aucun fournisseur).

## Ce qui reste en suspens

- Le message de collision de `persist` désigne la colonne principale (téléphone ou e-mail), même si la collision vient du pseudonyme. C'est rare, et le refus reste propre.
