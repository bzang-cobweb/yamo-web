# Date d'une carte : l'heure, « hier », « il y a N jours », puis la date en chiffres

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« date de la notification garder le format hier, 10:30, ... lorsque c'est encore possible au format du message précédent »)
- **Complète** : 2026-11-22-date-entiere-chiffres.md

## Décision

La date d'une carte dans l'app (notifications, calendrier d'une offre, listes de demandes), comme dans une messagerie :

| Quand | Affichage |
|---|---|
| Aujourd'hui | l'heure, au format de la langue (« 10:30 ») |
| Hier | « hier » |
| De 2 à 6 jours | « il y a 4 jours » |
| Au-delà | la date entière en chiffres, au format de la langue (« 03/03/2027 ») |

Les jours se comptent au calendrier : un message envoyé à 23:50 la veille affiche « hier ». « À l'instant », « il y a 5 min » et « il y a 3 h » disparaissent, ainsi que leurs textes (`justNow`, `minutesAgo`, `hoursAgo`).

## Motif

L'heure du jour se lit plus vite qu'une durée relative, et elle reste juste sans rafraîchir l'écran.

## Portée

Fonction `relativeTime` (mobile), commune à toutes les cartes.
