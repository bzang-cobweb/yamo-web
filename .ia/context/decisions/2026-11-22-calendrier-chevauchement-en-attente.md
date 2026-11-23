# Calendrier d'un logement : deux séjours en attente sur la même nuit, en rouge

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« dans le calendrier studio meublé Akwa, il y a 2 demandes en attente qui se chevauchent mais ces dates ne sont pas en rouge »)

## Décision

Une nuit est en « Chevauchement » (rouge) quand :
- un séjour en attente tombe sur une nuit déjà prise (acceptée ou terminée) ou bloquée ;
- **ou deux séjours en attente, ou plus, couvrent la même nuit** (nouveau) ;
- ou une visite en attente tombe sur un jour bloqué.

Deux visites en attente le même jour ne sont pas un chevauchement : elles peuvent avoir lieu toutes les deux. Les demandes expirées ne comptent pas.

## Portée

`OfferCalendar::month` (l'état `conflict` de chaque jour).
