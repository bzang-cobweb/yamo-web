# Calendrier d'un logement : deux visites en attente le même jour, en rouge

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« le calendrier pour une demande de visite permet le chevauchement des dates ? le marquer quand même en rouge sur le calendrier »)
- **Complète** : 2026-11-22-calendrier-chevauchement-en-attente.md

## Décision

Plusieurs visites peuvent toujours être demandées, et acceptées, pour le même jour : rien ne les bloque. Mais un jour où tombent au moins deux visites en attente s'affiche en rouge, « Chevauchement », comme deux séjours en attente sur la même nuit. Cela vaut aussi pour la seconde date proposée d'une visite. Une visite en attente sur un jour bloqué reste en rouge.

## Portée

`OfferCalendar::month` (l'état `conflict` de chaque jour).
