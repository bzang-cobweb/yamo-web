# `2026-11-19` — Ombre du cœur, plus de nombre de chambres dans l'item, onglets de l'offre au back-office

- **Remplace** : en partie `2026-11-18-unite-prix-chambres.md` (le nombre de chambres avant le prix dans les listes).
- **Type** : décision
- **Question posée** : demande directe : « ajouter une ombre au cœur blanc pour la visibilité », « enlever le nombre de chambres dans highlight de ListItem », « backoffice faire des tabs pour l'offre ».
- **Réponse** : la demande.
- **Ce qui est retenu** :
  - **app** : le cœur de favori a une ombre noire légère, pour rester lisible sur une photo claire ;
  - **listes** : la ligne en gras d'un logement ne montre plus le nombre de chambres, seulement le prix (« 60 000 FCFA / jour ») ; « Mes logements » garde le statut devant (« Publiée · … ») :
    - `RentalDomain::itemDetails()` est retiré ;
    - le mécanisme générique `Domain::itemDetails()` reste, vide par défaut ;
  - **back-office** : le formulaire d'une offre est en onglets :
    - « Annonce » : propriétaire, pays, textes, catégorie, prix, adresse ;
    - « Accès » ;
    - « Caractéristiques » ;
    - « Photos ».

    L'onglet ouvert est gardé dans l'URL. Les avis restent sous le formulaire.
- **Motif** : la demande de l'humain.
- **Portée** : `FavoriteHeart`, `RentalDomain`, formulaire Filament des offres.
- **Corollaires** : la traduction `bedrooms_count` est retirée.
- **Tranché par** : humain
