# Suppression de son compte

Demande du 2026-11-20.

## Ce que j'ai compris

L'utilisateur connecté peut supprimer son propre compte depuis l'app.

- **Entrée** : un lien « Supprimer mon compte » en bas de la page Profil, sous la déconnexion, en rouge. Il ouvre un formulaire du profil (une sous-page, avec son en-tête de retour).
- **Formulaire** : servi par l'API, comme les autres formulaires du profil. Il porte un texte d'avertissement (suppression définitive des données, de la photo et des connexions) et une confirmation, selon la réponse à la question 1. Le bouton d'envoi est rouge.
- **Suppression** (`DELETE /api/v1/auth/me`) :
  - le compte est désactivé, puis purgé après le délai réglé ;
  - ses jetons (toutes les connexions de ses appareils) sont supprimés tout de suite.
- **Après** : l'app oublie la session et revient à l'écran d'accueil de l'invité, avec un message de confirmation.
- **Registre** : la décision `2026-11-15-pas-de-suppression-de-son-compte` est remplacée par une nouvelle entrée, selon la réponse à la question 3. Dans le back-office, un administrateur ne peut toujours pas supprimer son propre compte depuis la liste des utilisateurs.
- Textes en français et en anglais, servis par l'API.

## Réponses à la validation

1. **Confirmation par le mot de passe actuel** (défaut du contrat, non contesté).
2. **Suppression récupérable** : le compte est désactivé (suppression douce), puis purgé définitivement après un délai réglé dans la configuration et le `.env` (`AUTH_ACCOUNT_DELETION_GRACE_DAYS`, 30 jours par défaut).
   - Pendant ce délai, un administrateur peut le restaurer depuis le back-office.
   - La photo et les données partent avec la purge. Les jetons sont supprimés tout de suite.
3. **Seul le dernier super administrateur ne peut pas supprimer son compte** (défaut du contrat, non contesté). Il reçoit un message qui l'explique.

## Critères de succès

- Depuis Profil, un utilisateur ouvre « Supprimer mon compte » et confirme avec son mot de passe.
  - Son compte est désactivé : il ne peut plus se connecter, et ses jetons ont disparu.
  - L'app revient à l'écran d'accueil.
- Après le délai réglé, une tâche planifiée purge le compte, avec sa photo. Avant ce délai, un administrateur le restaure depuis le back-office.
- Une confirmation incorrecte est refusée, et le compte reste intact.
- Le cas du super administrateur suit la réponse à la question 3.
- Tests web et mobile verts. OpenAPI régénéré. Aucune dépendance ajoutée.
