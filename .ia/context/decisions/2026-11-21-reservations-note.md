# « Mes réservations » : la note des avis à droite, comme à l'accueil

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-21-reservations-prix-comme-accueil.md

## Décision
Une carte de « Mes réservations » affiche la note moyenne des avis du logement, à droite de la
ligne en gras, comme à l'accueil. Les étoiles et le nombre d'avis viennent de `ListItem::rating`.
Un logement sans avis n'a pas de note.

`BookingListing` charge la moyenne et le nombre d'avis avec l'offre (`withReviewSummary`), en
une seule requête pour toute la page.

## Motif
Demande de l'humain du 2026-11-21 : « remettre la note de l'avis à droite comme sur les cards de
l'accueil ».
