Remplace en partie : 2026-11-23-accueil-carrousels-par-ville.md (taille des cartes ; plus de « sans lien Voir tout »).

# `2026-11-23` — Accueil : une flèche par ville, deux cartes à l'écran

- **Type** : décision
- **Question posée** : « Ajouter un lien Voir tout vers la recherche filtrée sur la ville ? » → « Oui » ; puis « remplacer voir tout par une flèche », « couleur noir pour l'icône flèche », « les cards de l'accueil doivent montrer 2 cards donc faire des cards plus petites », « diminuer encore la taille de la police », « la taille de la note sur les avis ne diminue pas », « diminuer encore la police ».
- **Réponse** : les demandes elles-mêmes.
- **Ce qui est retenu** :
  - à droite du nom de la ville, une flèche noire (`arrow.right`) ; lue « Voir tout <ville> » par les lecteurs d'écran ; elle ouvre l'onglet « Recherche » avec le seul filtre « Ville » sur cette ville ;
  - deux cartes côte à côte, la largeur utile partagée en deux ; le défilement va carte par carte ;
  - sur l'accueil, les cartes en police 11 (titre, sous-titre, prix) et étoiles 10 (`OfferCard` `compact`) ; la recherche garde ses tailles.
- **Motif** : les demandes de l'humain.
- **Portée** : `HomeScreen`, `OfferCard`, `ItemReviews` de l'app ; texte `home.seeAll` (fr, en, de).
- **Corollaires** : choisir la ville remplace les autres filtres de la recherche.
- **Tranché par** : humain
