# Dates : le mois en lettres, jamais en chiffres

Remplace : 2026-11-22-date-entiere-chiffres.md, 2026-11-22-demandes-date-chiffres.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« pour éviter toute ambiguïté mois vs jour en chiffre, annuler le format dd/mm/yyyy »)

## Décision

On n'écrit plus de date entièrement en chiffres (03/03/2027) :

- **Textes des notifications** (`:period`) : le mois en lettres, « du 5 déc. 2026 au 7 déc. 2026 », « le 28 nov. 2026 ».
- **Date d'une carte dans l'app**, avant hier : « 3 mars 2027 10:30 » (mois abrégé, format de la langue). « À l'instant », « il y a 5 min », l'heure du jour et « hier à 10:30 » ne changent pas (2026-11-22-date-carte-heure, 2026-11-22-date-carte-hier-a).

## Motif

Le format en chiffres varie d'une langue à l'autre (03/04 se lit 3 avril ou 4 mars). Le mois en lettres ne se confond pas.

## Portée

`Booking::period` (le paramètre `numeric` est retiré) et `relativeTime` (mobile). Les notifications enregistrées ont été réécrites (migration 2026_11_30_200000, relancée en local).
