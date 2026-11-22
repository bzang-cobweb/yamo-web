# Filtre « Type » des notifications : une option par action

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« adapter le filtre type de notification »)
- **Complète** : 2026-11-22-notification-par-action.md

## Décision
Le filtre « Type » propose :
- le groupe « Demande de logement » (`booking`), toutes actions ;
- puis une option par action présente dans les notifications du compte (`booking_received`, `booking_updated`, `booking_accepted`, `booking_refused`, `booking_cancelled`), du nom de sa carte.

Les filtres statut, logement et type de location s'affichent pour le groupe et pour chaque action.

## Motif
Le groupe garde les liens existants : les compteurs de « Mes logements » filtrent sur `booking`.

## Portée
`OfferNotificationFilter::types()`, `NotificationFilters`.
