# « Demandes » d'une offre : une liste à elle, plus une copie de « Mes réservations »

Remplace : 2026-11-21-demandes-comme-mes-reservations.md (cartes et filtres) ; complète 2026-11-21-demandes-titre-sous-filtres.md

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« enlever le titre du logement sur les cards demandes / mettre le titre composé en
  dessous du titre et l'enlever aussi des cards / ne plus utiliser l'écran mes réservations la dupliquer et
  faire mes demandes pour une offre »)

## Décision

- `offer.bookings.received` devient la liste des demandes d'une offre, avec son code à elle : elle ne
  reprend plus la carte ni les filtres de « Mes réservations ».
- Sous les filtres : le titre du logement en gras et, dessous, sa ligne du type
  (« Location · Appartement · Douala »).
- Les cartes ne répètent pas le logement : en titre, le type de demande et les dates
  (« Visite · le 23 nov. 2026 ») ; la photo, le statut dessus ; en gras, le total d'un séjour (rien pour
  une visite). Ni note ni prix par jour ou par mois : les mêmes sur toutes les cartes d'un logement.
- Filtres : statut réel, période ; le logement reste en champ caché. Ville, logement et types de l'offre
  sont retirés : il n'y a qu'un logement.
- Pas de recherche : elle cherchait dans le titre du logement.
- Inchangé : la carte ouvre la page de la demande ; Accepter, Refuser, Masquer / Afficher l'adresse
  dans le menu trois points.

## Mécanique

Carte : `Lists/OfferRequestItem`. Intitulé : `headingFor()` sert `{title, subtitle}`, que l'app affiche
sur deux lignes. `FiltersBookings` ne sert plus que « Mes réservations ».

## Décidé seul (à confirmer)

Retirer la note, le prix par jour ou par mois et la recherche ; mettre le total en gras.
