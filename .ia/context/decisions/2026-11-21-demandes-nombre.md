# « Demandes » d'une offre : « x demandes » sous les filtres, recherche sur le membre

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« mettre le nombre de demandes: x demandes et enlever le titre composé » ;
  « remettre la barre de recherche »)

## Décision

- Sous les filtres, à la place de la ligne du type : le nombre de demandes de la liste, recherche et
  filtres compris (« 3 demandes », « 1 demande », « Aucune demande »).
- La barre de recherche revient. Il n'y a qu'un logement : elle cherche dans le nom du membre (prénom,
  nom, pseudonyme) et dans son message (« Rechercher un membre, un message… »).

## Mécanique

`Listing::headingFor($filters, $search)` reçoit la recherche ; `Listing::countFor()` compte la liste
comme elle s'affiche. Clés `offer::booking.profile.requests_count` et `offer::booking.list.search_requests`.

## Décidé seul (à confirmer)

Ce sur quoi porte la recherche : le membre et son message.

## Corollaires

Remplace, pour l'intitulé et la recherche, 2026-11-21-demandes-d-une-offre et 2026-11-21-demandes-titre-logement-barre.
