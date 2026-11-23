Remplace en partie : 2026-11-23-onglet-recherche.md (l'accueil n'est plus vide).

# `2026-11-23` — Accueil de l'app : un carrousel par ville

- **Type** : décision
- **Question posée** : « sur l'accueil, prévoir un carousel par ville qui va afficher de manière aléatoire 10 offres dans cette ville ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `GET /api/v1/home` (compte facultatif) : pour chaque ville qui a au moins une offre visible du pays courant, par ordre alphabétique, 10 offres visibles de cette ville tirées au hasard à chaque appel (`OfferBrowseListing::cityCarousels()`) ;
  - les cartes sont celles de la recherche (photo, cœur, titre, sous-titre, prix, note) ; toucher une carte ouvre la fiche ;
  - dans l'app, le nom de la ville au-dessus, les cartes défilent de côté, la suivante dépasse ; tirer vers le bas refait un tirage ;
  - la carte d'offre est un composant commun à l'accueil et à la recherche (`OfferCard`).
- **Motif** : la demande de l'humain.
- **Portée** : `HomeController`, route `v1.home`, `OfferBrowseListing` ; `HomeScreen`, `OfferCard`, `BrowseScreen` de l'app.
- **Corollaires** : pas de limite au nombre de villes ni de lien « Voir tout » vers la recherche filtrée ; une requête par ville.
- **Tranché par** : humain
