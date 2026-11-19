Remplace en partie : 2026-11-19-contrat-adresse-offre-table.md (l'indication était non traduite).

# `2026-11-19` — L'indication de l'adresse d'une offre est traduisible

- **Type** : décision
- **Question posée** : demande directe de l'humain : « la description de OfferAddress doit être traduisible ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `offer_addresses.indication` est rangée par langue, comme le titre et la description de l'offre (500 caractères par langue) ;
  - un texte manquant s'affiche dans la langue d'origine de l'offre, puis la langue par défaut (`OriginalLocale`) ;
  - l'app l'enregistre dans la langue de saisie (étape « Langue » à l'édition), un champ vide retire cette langue seule ; le back-office a un onglet par langue ;
  - une indication existante est rangée dans la langue d'origine de l'offre (le français à défaut).
- **Motif** : demande de l'humain ; l'indication est un texte lu par les visiteurs, comme la description.
- **Portée** : `OfferAddress`, `OfferService::syncAddress()`, `OfferForm` (app et back-office), `OfferDetailResource`.
- **Corollaires** : une indication saisie dans une autre langue avant ce changement avait remplacé le texte unique ; la migration l'a rangée dans la langue d'origine de l'offre.
- **Tranché par** : humain
