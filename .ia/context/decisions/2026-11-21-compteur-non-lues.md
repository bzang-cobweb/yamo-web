# « Mes logements » : un second bouton « x notifications non lues »

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-compteur-vers-notifications.md, 2026-11-21-filtre-non-lues.md

## Décision

Une carte de « Mes logements » a, à côté de « x demandes en attente », un bouton du même style
« x notifications non lues ». Il ouvre les notifications avec les mêmes filtres que le premier
(type demande de logement, statut en attente, ce logement) plus « Non lues : Oui ». Il compte
donc les demandes en attente de ce logement dont une notification au moins n'est pas lue, une
demande comptant pour une. À 0, pas de bouton.

`ListItem::counter()` peut s'appeler plusieurs fois : `counters` sert la liste, dans l'ordre ;
`counter` reste le premier, pour une app qui n'en connaît qu'un.

## Motif

Demandes de l'humain du 2026-11-21 : « mes logements cards ajouter un bouton comme x demande en
attente pour x notifications non lues » ; « ce bouton a les mêmes filtres que x demandes en
attente mais lui il rajoute le filtre non lues à oui ».
