# Date d'une carte : l'heure, « hier 10:30 », puis la date en chiffres et l'heure

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« Aujourd'hui : l'heure, 10:30 / Hier : hier 10:30 / Au-delà : 03/03/2027 10:30 », après avoir demandé de garder « à l'instant », « il y a 5 min »)
- **Complète** : 2026-11-22-date-entiere-chiffres.md

## Décision

La date d'une carte dans l'app (notifications, calendrier d'une offre, liste des demandes) :

| Quand | Affichage |
|---|---|
| Moins d'une minute | à l'instant |
| Moins d'une heure | il y a 5 min |
| Plus tard aujourd'hui | 10:30 (format de la langue) |
| Hier | hier 10:30 |
| Avant | 03/03/2027 10:30 (date et heure au format de la langue) |

Les jours se comptent au calendrier : un message de 23:50 la veille affiche « hier 23:50 ».

## Motif

On voit tout de suite le moment exact, sans calcul.

## Portée

Fonction `relativeTime` (mobile). Les textes `hoursAgo` et `daysAgo` ne sont plus affichés, mais restent servis, comme l'humain l'a demandé.
