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

## Ce que j'ai décidé seul

- **Suppression douce pour tout le monde :** depuis le back-office aussi, une suppression devient récupérable jusqu'à la purge. Le modèle `User` porte `SoftDeletes`, et un administrateur peut purger sans attendre (« Supprimer définitivement »).
- **Restauration et purge dans le back-office :** elles demandent la permission de suppression des utilisateurs, sans permission nouvelle. Purger son propre compte est refusé, comme le supprimer.
- **Photo :** elle reste jusqu'à la purge, pour qu'une restauration la retrouve. Les jetons, eux, partent tout de suite.
- **Réservation pendant le délai :** l'e-mail, le téléphone et le pseudonyme d'un compte supprimé restent réservés. `UniquePhone` et `Challenges::ensureFree` comptent les comptes supprimés, parce que les index uniques de la base les comptent.
- **Message après suppression :** une alerte système « Votre compte a été supprimé. », puis l'oubli de la session et le retour à l'accueil.
- **Bouton et lien rouges :** la couleur d'erreur des formulaires (`ERROR_COLOR`) est réutilisée, et une option `destructive` a été ajoutée au formulaire dynamique.
- **Délai :** `AUTH_ACCOUNT_DELETION_GRACE_DAYS=30` est écrit en clair dans `.env.example` et dans `.env`. Les autres variables d'auth y sont en commentaire.
- **Retouches :** la barre du logo revient sur l'onglet Accueil, ce qui annule le point 4 du chantier précédent. L'espace sous le sélecteur de langue passe à 24 points.

## Ce qui a été fait

- API :
  - `DELETE /api/v1/auth/me` : formulaire `auth.profile.delete-account`, `AccountDeletionService`, refus du dernier super administrateur ;
  - commande `auth:purge-deleted-accounts`, planifiée chaque jour ;
  - migration `deleted_at` ;
  - textes en français et en anglais ;
  - `openapi.json` régénéré.
- Back-office : filtre « Comptes supprimés », colonne « Supprimé le », actions « Restaurer » et « Supprimer définitivement ».
- Mobile :
  - lien rouge sur Profil, écran de suppression avec bouton rouge, service ;
  - `ApiClient.delete` accepte un corps ;
  - barre du logo sur Accueil ;
  - espace sous le sélecteur de langue.
- Tests :
  - web : 216 tests (12 sur la suppression, 2 sur la restauration, 1 sur la course entre code et suppression) ;
  - mobile : 93 tests.
- qa-check : NO-GO au premier passage (`ensureFree`), puis GO.
- Vérifié dans le simulateur, avec un compte jetable purgé ensuite :
  - parcours complet ;
  - mauvais mot de passe refusé, puis suppression ;
  - retour à l'accueil ;
  - en base : compte désactivé, plus aucun jeton, restaurable.

## Ce que je n'ai pas vérifié

- La restauration dans le back-office à l'écran : le navigateur intégré refuse le certificat DDEV. Elle est couverte par les tests seulement.
- Le passage réel du planificateur : il faut que le cron `schedule:run` tourne sur le serveur.
- Les codes de changement d'e-mail ou de téléphone face à un compte supprimé entre l'envoi et la validation. Seule l'inscription est testée, et la règle est commune (`ensureFree`).

## Ce qui reste en suspens

- **Mise en production :** il faut ajouter le cron `* * * * * php artisan schedule:run`, sinon la purge ne tourne jamais.
- **Texte de l'avertissement :** il dit « contactez-nous pour le récupérer », mais aucun moyen de contact n'est défini dans l'app.
