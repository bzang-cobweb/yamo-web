# `2026-11-25` — Tous les avis d'une offre en liste, comme « Mes logements », avec la recherche

Remplace : 2026-11-25-liste-avis-titre-nombre.md

- **Type** : décision
- **Question posée** : « faire le rating une liste comme mes logements avec la recherche ».
- **Ce qui est retenu** :
  - **Une liste servie par l'API**, `offer.reviews` (`OfferReviewListing`), affichée par l'écran générique des listes, comme « Mes logements ».
    - L'offre est un filtre caché, choisi par « Voir tous les 3 avis » de la fiche (route `/offers/[id]/reviews`).
    - Une restriction toujours appliquée : seuls les avis de cette offre, et aucun sans offre visible ou si l'offre ne montre pas ses avis.
    - La liste est publique, comme la fiche.
  - **En tête de la liste** :
    - en titre, « Avis (4,7) », avec la flèche de retour seule (`closable={false}`) ;
    - la recherche (« Rechercher… »), sur le commentaire et le prénom ;
    - « 3 avis », recherche comprise.
  - **Un avis par carte**, disposé comme sur la fiche (« garder la même disposition des contenus que les avis sur la page de l'offre ») : le prénom ; les étoiles et la date séparées par un point ; le commentaire en entier. Le serveur le demande par `ListItem::reviewLayout()` (`layout: review`).
  - **Le style de ces cartes** : une ligne au-dessus seulement, sans cadre ni marge intérieure (« seulement une bordure en haut et sans padding »). Un peu d'air reste entre la ligne et le prénom.
  - **Pas de lien « Filtres »** quand une liste n'a que des filtres cachés.
  - **Choix de l'offre** : la fiche choisit l'offre avant d'ouvrir la liste ; un lien direct la choisit après le premier rendu.
  - **Le reste** :
    - nouveau crochet `Listing::constrain()`, toujours appelé, sans effet par défaut ;
    - l'ancien écran propre aux avis (`OfferReviewsScreen`) est retiré de l'app ;
    - le point d'API `GET /offers/{offer}/reviews` reste, mais l'app ne s'en sert plus.
- **Tranché par** : humain ; session (la recherche sur le prénom et le commentaire, le crochet `constrain`)
