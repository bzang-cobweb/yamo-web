# `2026-11-19` — Back-office d'une offre : onglets « Localisation » et « Informations »

- **Type** : décision
- **Question posée** : « en back-office créer un tab localisation : adresse, indication, un tab informations ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - onglet « Localisation » : l'adresse et son indication (par langue), sortis de « Annonce » ; masqué pour un domaine sans adresse ;
  - onglet « Informations » : les blocs d'informations, sortis de « Règles » ;
  - ordre des onglets calqué sur les étapes de l'app : Annonce, Accès, Caractéristiques, Règles, Informations, Localisation, Photos ; « Règles » masqué pour un domaine sans règles.
- **Motif** : la demande de l'humain ; l'ordre, par défaut de l'agent, suit celui du formulaire de l'app.
- **Portée** : `Modules/Offer/app/Filament/Resources/OfferResource/Schemas/OfferForm.php`, textes `offer::offer.tabs`.
- **Corollaires** : aucun changement de données ni d'API.
- **Tranché par** : humain (demande) ; ordre par défaut de l'agent
