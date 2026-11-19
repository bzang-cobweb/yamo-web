Remplace en partie : 2026-11-19-fiche-offre-ordre.md (la ville quitte les caractéristiques).

# `2026-11-19` — Fiche d'une offre : la ville n'est plus dans les caractéristiques

- **Type** : décision
- **Question posée** : demande directe de l'humain : « enlever la ville dans les caractéristiques du détail de l'offre ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - les caractéristiques commencent par le type de logement, meublé, la catégorie, puis les autres attributs ;
  - la ville reste visible dans l'adresse du bloc « Localisation » (décision 2026-11-19-fiche-localisation-accordeon) et dans `location` (servi, inchangé).
- **Motif** : demande de l'humain ; la ville faisait doublon avec l'adresse complète.
- **Portée** : `OfferDetailResource::facts()`, `RentalDomain::leadingFacts()`.
- **Corollaires** : une offre sans adresse n'affiche plus sa ville nulle part sur la fiche (sans adresse, il n'y a pas de ville non plus).
- **Tranché par** : humain
