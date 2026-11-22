# Carte de notification : la ville dans le titre, plus de statut

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain, dans le chat (« enlever le statut et ajouter la ville »)

## Décision
Le titre de la carte d'une notification de demande devient « type de location · type de logement · ville » (« Location meublée · Studio · Douala »). La pastille du statut n'est plus sur la carte ; elle reste sur la page du détail.

## Motif
Situer le logement dès la carte ; le statut se lit sur le détail.

## Portée
`OfferNotificationFilter::cardTitle()`, `NotificationsScreen` (app). L'API sert toujours `tag`.

## Corollaires
Complète 2026-11-22-carte-notification-type-titre-auteur.
