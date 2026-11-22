Remplacée par : 2026-11-22-dates-sans-chiffres.md

# Dates entières en chiffres, au format de la langue

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« la date doit être au format dd/mm/yyyy lorsque l'affichage est entier », « format dd/mm/yyyy adapté à la langue », « adapter les textes de demande annulée sur le même principe » → « Dates des textes en chiffres »)
- **Complète** : 2026-11-22-demandes-date-chiffres.md

## Décision

- **Date d'une carte dans l'app** (notifications, calendrier d'une offre, listes) : au-delà d'une semaine, à la place de « hier » ou « il y a 4 jours », la date entière s'écrit toujours en chiffres, au format de la langue : 03/03/2027 (fr), 03.03.2027 (de), 03/03/2027 dans l'ordre mois/jour (en). Fait dans `relativeTime` (mobile), sans option.
- **Textes des notifications** (`:period`, toutes les étapes) : la période s'écrit en chiffres, au format de la langue (« du 05/12/2026 au 07/12/2026 », « le 28/11/2026 ou le 30/11/2026 »). Fait avec `Booking::period($locale, numeric: true)` dans `BookingText`.

## Motif

Une date en chiffres est plus courte et se lit d'un coup d'œil.

## Portée

Les autres affichages de la période ne changent pas : lignes du détail, cartes des listes, confirmation d'acceptation (« 5 déc. 2026 »). Les notifications déjà envoyées gardent leur texte figé.

## Corollaires

La demande portait sur l'annulation. Le remplacement `:period` est commun à toutes les étapes, donc tous les textes de notification changent.
