Remplacée en partie par : 2026-11-19-fond-blanc-filtres.md (la phrase de l'item n'a plus la couleur primaire)
# `2026-11-19` — Avis en onglet de l'offre, section « Accès », date « Payée jusqu'au », retouches de l'item de liste

- **Remplace** : partiellement `2026-11-19-contrat-avis-offres.md` (la page « Avis » du back-office devient un onglet de l'offre) et `2026-11-19-item-titre-saisi-details.md` (la ligne de note de l'item).
- **Type** : décision
- **Question posée** : demande directe en 7 points, puis deux précisions : « Tab only », « Drop the dark circle ».
- **Ce qui est retenu** :
  - **back-office** :
    - les avis d'une offre sont un onglet de sa fiche (`ReviewsRelationManager` : filtre par note, suppression, ni création ni modification) ; la page « Avis » du menu est retirée ;
    - une colonne `paid_until` (« Payée jusqu'au »), sans effet pour l'instant : plus tard, une offre non payée ne sera plus visible ;
    - une section « Accès » réunit statut, « En vedette jusqu'au », « Disponible dès le » (attribut du domaine, déclaré par `Domain::accessAttributes()`) et « Payée jusqu'au » ;
  - **app** (pour tout item de liste) :
    - plus de chevron à droite ;
    - la phrase mise en avant (`highlight`) est en gras et dans la couleur primaire ;
    - la note n'est plus une ligne « ★ 4,5 (12) » : seules les étoiles, en bas à droite, sur la ligne de la phrase (note et nombre lus par l'accessibilité) ;
    - le cœur de favori n'a plus de fond rond : vide et blanc, ou plein et rouge.
- **Motif** : la demande de l'humain.
- **Portée** : ressource Filament des offres, `Domain`, `RentalDomain`, `Offer` ; `ItemReviews`, `FavoriteHeart`, `ProfileListScreen`.
- **Corollaires** :
  - la permission `offer_reviews.view` ouvre l'onglet, `offer_reviews.delete` permet de supprimer ;
  - le cœur blanc sans fond peut être moins visible sur une photo claire.
- **Tranché par** : humain
