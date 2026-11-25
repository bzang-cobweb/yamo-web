# `2026-11-25` — L'accueil tient quand il y a beaucoup d'offres

- **Type** : décision
- **Question posée** : « si demain il y a beaucoup d'offres, est-ce que "À la une" peut poser problème ? » ; puis « faire les points 1, 3 et 4 et laisser de côté le point 2 (le cache) ».
- **Ce qui est retenu** :
  - **1. La note sur l'offre** : `offers.reviews_avg_rating` (décimal) et `offers.reviews_count`.
    - Un avis donné, changé ou retiré les recalcule (`OfferReview` : `saved`, `deleted`). Retirer son avis depuis l'app passe maintenant par le modèle.
    - Les offres existantes ont reçu leur note : en local, 11 offres pour 17 avis.
    - `withReviewSummary()` ne calcule plus rien, et les listes lisent ces colonnes.
    - Le complément d'« À la une » trie sur un index (pays, statut, note).
  - **3. Les carrousels des régions** ne font plus `ORDER BY RAND()` sur toute la région.
    - Le tirage se fait dans l'app, parmi les identifiants des 200 offres les plus récentes de la région (`CAROUSEL_WINDOW`).
    - Les offres remontées passent d'abord (décision 2026-11-25-remontee-carrousel).
    - Une offre plus ancienne que les 200 dernières d'une région n'apparaît plus dans son carrousel, mais reste dans la recherche.
  - **4. Un index sur `offers.published_at`**, pour les tris par date et la fenêtre des carrousels.
  - **2. Pas de cache de l'accueil** pour l'instant.
- **Pas couvert** : un avis supprimé par la suppression d'un compte (en cascade dans la base) ne recalcule pas la note de l'offre. Les listes des régions et des villes se recalculent toujours à chaque affichage.
- **Tranché par** : humain (1, 3 et 4, pas 2) ; session (200 offres, la note recalculée par les événements du modèle)
