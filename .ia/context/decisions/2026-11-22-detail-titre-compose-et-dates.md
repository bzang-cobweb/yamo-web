Remplacée par : 2026-11-22-detail-lignes-titre-dates-adresse.md (pour le titre composé et les dates ; la barre du haut reste)

# Page d'une demande : « Ma réservation », titre composé et dates sur leurs lignes

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« détail demande depuis mes réservations titre de la page "Ma réservation" / détail demande mettre titre composé sur une ligne / date.s sur une autre ligne »)
- **Complète** : 2026-11-22-detail-titre-compose.md

## Décision

- **Barre du haut** : « Ma réservation » pour le membre qui a fait la demande (en : My booking, de : Meine Buchung). Pour le propriétaire : « Demande reçue » (en : Request received, de : Erhaltene Anfrage). Le titre composé n'y est plus, puisqu'il passe dans la page.
- **En tête de la page**, sous la pastille du statut et le nom du logement :
  - une ligne avec le titre composé sans ville (« Location meublée · Studio »), en gris ;
  - une ligne avec les dates (« Du 5 déc. 2026 au 7 déc. 2026 », « Le 5 déc. 2026 » pour une visite).

## Portée

`AddBookingToNotificationThread` (`title`, et `booking.composed_title` et `booking.period`, nouveaux) et `NotificationDetailScreen` (mobile). Textes `detail.mine` et `detail.received`.
