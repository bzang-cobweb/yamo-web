# Détail d'une réservation : « Modifier » tant qu'elle est en attente

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : « détail réservation bouton mes réservations remplacer par modifier si réservation encore en attente »

## Décision
Sur la page de la notification d'une demande, pour le membre qui l'a faite et tant qu'elle
est en attente, le bouton du bas devient « Modifier » et ouvre le formulaire prérempli
(`/profile/offer.booking.edit?record=<id>`). Répondue ou annulée, il redevient
« Mes réservations ». Le propriétaire garde « Traiter la demande ».

## Motif
Le statut change après l'envoi de la notification : le lien est donc calculé à la lecture
(`AddBookingToNotificationThread`), pas figé dans ses données.

## Portée
Page d'une notification du fil d'une demande. Au retour sur cette page (formulaire
enregistré), l'app relit la demande pour afficher ses nouvelles dates.

## Corollaires
Les cartes de la liste des notifications gardent le lien stocké ; seule la page change.
