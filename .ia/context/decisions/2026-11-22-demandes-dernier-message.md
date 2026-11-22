# « Demandes reçues » : le dernier message sur la carte, sans menu ⋮

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« demandes recues cards afficher première ligne dernier message et une pastille avec le
  nombre de notification non lues » ; « enlever le dropdown des 3 petits point »)

## Décision

- Sur une carte de « Demandes reçues », sous les dates : la première ligne du dernier message du fil de la demande
  (du membre ou du propriétaire), citée, tronquée à une ligne ; sans message dans le fil, celui de la demande ;
  sans message, rien.
- Le nombre de notifications non lues reste en pastille rouge à côté du statut
  (2026-11-22-demandes-pastille-non-lues-total).
- Plus de menu ⋮ : Accepter, Refuser, Masquer / Afficher l'adresse s'affichent en boutons sur la carte
  (décision 2026-11-22-demandes-sans-menu).

## Mécanique

`ReceivedBookingListing` lit le dernier `data->message` des notifications du fil (`data->booking_id`) ; l'app
tronque la citation d'une carte à une ligne.

## Décidé seul (à confirmer)

Les actions en boutons sur la carte (plutôt que les retirer).
