# « Terminée » devient un statut enregistré ; planificateur dans ddev

Remplace : 2026-11-22-statuts-terminee-expiree.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« ajoute le planificateur à ddev / ajoute la commande pour passer au statut terminé également »)

## Décision

- **Terminée** est maintenant un statut enregistré (`BookingStatusEnum::COMPLETED`, « Terminée », gris). Chaque nuit à 00:05, `booking:complete` (déclarée dans `routes/console.php`) passe au statut « Terminée » les demandes acceptées dont le dernier jour est passé : le départ d'un séjour, la dernière date proposée d'une visite. Personne n'est prévenu.
- En attendant cette exécution, une demande acceptée et passée s'affiche déjà « Terminée » et compte déjà comme terminée (`isCompleted`, scope `completed`).
- Une demande terminée garde la nuit prise dans le calendrier, compte dans les séjours d'un membre (`BookingStatusEnum::held()` = acceptée ou terminée), donne le droit d'écrire un avis et reçoit l'invitation.
- **Expirée** ne change pas : elle reste affichée d'après les dates, sans être enregistrée (règles de 2026-11-22-statuts-terminee-expiree).
- Le filtre « Statut » des listes et des notifications gagne l'option « Terminée ».
- **ddev** : un démon `scheduler` (`php artisan schedule:work`) tourne à côté de la file d'attente (`.ddev/config.yaml`). Le planificateur lance `booking:complete` (00:05), `booking:invite-reviews` (10:00), `notification:prune` et `model:prune`.

## Portée

Pas de migration : la colonne `status` est une chaîne. En production, il faut le cron `* * * * * php artisan schedule:run`.
