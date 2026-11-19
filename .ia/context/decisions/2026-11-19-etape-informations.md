# `2026-11-19` — Formulaire d'un logement : une étape « Informations » à part

- **Type** : décision
- **Question posée** : demande directe de l'humain : « faire une étape supplémentaire pour ces informations supplémentaires ».
- **Réponse** : la demande elle-même ; le détail est choisi par la session.
- **Ce qui est retenu** :
  - les blocs d'informations ont leur propre étape, « Informations » (« Information »), juste après les règles ;
  - l'étape des règles s'appelle désormais « Règles » (« Rules ») ;
  - étapes : Présentation, Caractéristiques, Prix et disponibilité, Règles, Informations, Adresse, Photos ;
  - le libellé « Informations » du champ est masqué (le titre de l'étape le dit), comme pour les photos ; son aide reste.
- **Motif** : demande de l'humain.
- **Portée** : `RentalDomain::formSteps()`, `OfferForm` (app), textes `offer::rental.steps`.
- **Corollaires** : complète 2026-11-19-contrat-regles-informations et 2026-11-19-contrat-offer-informations.
- **Tranché par** : humain (détail par la session)
