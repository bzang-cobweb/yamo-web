Remplace : 2026-11-15-pas-de-suppression-de-son-compte.md

# `2026-11-20` — Chacun peut supprimer son compte depuis l'app, sauf le dernier super administrateur

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-20-suppression-de-compte.md` (mode de confirmation, suppression définitive ou récupérable, cas du super administrateur).
- **Réponse** : « je valide mais le recoverable days doit être défini dans la config et le .env ».
  - **Confirmation** par le mot de passe actuel.
  - **Suppression récupérable** :
    - suppression douce, puis purge définitive après `config('auth.account_deletion.grace_days')` jours (`AUTH_ACCOUNT_DELETION_GRACE_DAYS`, 30 par défaut) ;
    - restauration possible par un administrateur pendant ce délai ;
    - jetons supprimés tout de suite.
  - **Le dernier super administrateur** ne peut pas supprimer son compte.
- **Motif** : laisser chacun quitter le service, sans perdre le dernier accès au back-office, avec un délai pour revenir sur une erreur.
- **Portée** : API (`DELETE /api/v1/auth/me`), app mobile (profil), back-office (restauration), tâche planifiée de purge.
- **Corollaires** :
  - pendant le délai, l'e-mail, le téléphone et le pseudonyme du compte restent réservés ;
  - dans le back-office, la règle de l'entrée remplacée tient toujours : on ne supprime pas son propre compte depuis la liste des utilisateurs.
- **Tranché par** : humain, avec les défauts du contrat pour la confirmation et le super administrateur
