# Page d'une demande : « Demande de séjour » ou « Demande de visite »

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« détail réservation titre demande séjour si location meublé et demande visite »)

## Décision

La barre du haut de la page d'une demande affiche « Demande de séjour » pour une réservation (location
meublée) et « Demande de visite » pour une visite, au lieu de « Demande de logement ». Les cartes de la liste
des notifications ne changent pas.

## Mécanique

`AddBookingToNotificationThread` remplace le titre de la page selon le type de la demande ; clés
`offer::booking.detail_title.stay|visit` (fr, en, de).

## Non vérifié

Le cas « visite » n'a pas de test de page dédié (le type vient de la demande, comme pour le séjour).
