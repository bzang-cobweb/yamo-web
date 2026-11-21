# « Mes réservations » : filtres sur les types réels de l'offre

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : « filtre type uniquement sur les véritables types des offres » puis « ajouter aussi le filtre type de location »
- **Complète** : 2026-11-21-reservations-filtre-statut.md

## Décision
Le filtre « Type » (séjour / visite, type de la demande) est retiré de « Mes réservations ». Il
est remplacé par les types de l'offre servis par le domaine (`Domain::offerTypeFilters()`), en
listes déroulantes : pour la location, « Type de logement » (appartement, maison, studio,
chambre) et « Type de location » (meublée, longue durée, colocation). Ils s'appliquent à l'offre
de la demande, même supprimée, par `Domain::applyFilters()`, comme dans la vitrine. Vide : tous.
Ordre : Statut, Période, Ville, Type de logement, Type de location.

## Motif
Séjour / visite découle du type de location : c'était un doublon, et pas un type d'offre.

## Portée
« Mes réservations » seulement. Toutes les valeurs du domaine sont proposées, pas seulement
celles des logements demandés. Un domaine sans types n'a pas ces filtres.

## Corollaires
La clé `offer::booking.profile.filter_type` est retirée.
