# « Expirée » devient un statut enregistré

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« enregistrer statut Expirée »)
- **Complète** : 2026-11-22-statut-termine-enregistre.md

## Décision

- **Expirée** est maintenant un statut enregistré (`BookingStatusEnum::EXPIRED`, « Expirée », gris). Chaque nuit à 00:05, `booking:expire` (déclarée dans `routes/console.php`) passe au statut « Expirée » les demandes restées en attente après leur date : l'arrivée d'un séjour, la dernière date proposée d'une visite. Personne n'est prévenu.
- En attendant cette exécution, une demande en attente et passée s'affiche déjà « Expirée » et compte déjà comme expirée (`isExpired`, scope `expired`).
- Les règles d'une demande expirée ne changent pas : plus de réponse, de modification ni d'annulation (403 « Cette demande a expiré »), hors des compteurs et filtres « En attente », dans les passées. Elle reste listée dans le panneau du jour du calendrier, sans couleur « en attente ».
- Le filtre « Statut » des listes et des notifications gagne l'option « Expirée ».

## Portée

Pas de migration : la colonne `status` est une chaîne.
