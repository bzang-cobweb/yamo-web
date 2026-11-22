# Carte de notification : type, titre du logement, auteur, message, date

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain, dans le chat (« notifications cards : type de location avec type de [logement], titre de l'offre, nom de l'auteur, message sans quote en italique, date alignée à droite »)

## Décision
La carte d'une notification de demande, dans l'onglet Notifications, montre dans l'ordre :
1. en gras, le type de location et le type de logement (`card_title`, « Location meublée · Studio ») ;
2. le titre saisi du logement, en gris, sur une ligne (`card_subtitle`, nouveau) ;
3. le nom de qui agit, sans lien ;
4. son message, en italique gris, sur une ligne, sans guillemets ;
5. la date, alignée à droite.

Le texte de la notification n'est plus sur la carte. Une notification sans auteur (ancienne, ou action du lecteur lui-même) garde son texte à la place du nom.

## Motif
Une carte qu'on lit d'un coup d'œil : quel logement, qui, ce qu'il a dit.

## Portée
`NotificationFilter::cardSubtitle()` (contrat du module Notification), `OfferNotificationFilter`, `NotificationResource`, `NotificationsScreen` (app). La page du détail ne change pas.

## Corollaires
`BookingResource::propertyType()` devient public statique, partagé avec la carte.
