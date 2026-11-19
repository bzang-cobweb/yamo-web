# `2026-11-19` — Back-office : loyer dans « Caractéristiques », type et meublé dans « Annonce »

- **Type** : décision
- **Question posée** : demande directe : « loyer va à caractéristiques avant charges mensuelles », « type de logement et meublé vont dans annonce avant catégorie ».
- **Réponse** : la demande.
- **Ce qui est retenu**, dans le formulaire d'une offre du back-office :
  - l'onglet « Annonce » : propriétaire, pays, titre et description, **type de logement**, **meublé**, catégorie, adresse ;
  - l'onglet « Caractéristiques » : surface, pièces, chambres, équipements, **loyer**, charges mensuelles ;
  - le placement vient du domaine, et le formulaire reste générique :
    - `Domain::listingAttributes()` (rental : type, meublé) ;
    - `Domain::priceBeforeAttribute()` (rental : charges ; sans valeur, le prix va à la fin de « Caractéristiques »).
- **Motif** : la demande de l'humain.
- **Portée** : formulaire Filament des offres ; `Domain`, `RentalDomain`.
- **Corollaires** :
  - le formulaire de l'app n'est pas touché (étapes déclarées à part) ;
  - « Charges mensuelles » (dans « Caractéristiques ») dépend de « Meublé » (dans « Annonce ») ; la condition marche d'un onglet à l'autre, mais ce n'a pas été revu dans un navigateur.
- **Tranché par** : humain
