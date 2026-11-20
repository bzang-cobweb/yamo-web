# Le titre d'une carte de demande : le type de location

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-type-et-statut.md, 2026-11-20-notification-generique.md

## Décision

Dans la liste des notifications, la carte d'une demande a pour titre le type de location
de son logement (« Location meublée ») au lieu de « Demande de logement ».

- Contrat `NotificationFilter::cardTitle()` ; `NotificationResource` sert `card_title`
  (`null` : `title`). Le module Notification reste générique.
- Le module Offer le tire de la demande, lue par `BookingCache` avec son logement (une
  requête pour toute la page).
- `title` ne change pas : la barre du haut de la page d'une notification et le filtre
  « Type » gardent « Demande de logement ».

## Motif

Demande de l'humain du 2026-11-20 : « dans les cards mettre le type de location logement à
la place du type de logement ».

## Portée

Les cartes de la liste des notifications. Pas les cartes du calendrier d'une offre (même
logement pour toutes).
