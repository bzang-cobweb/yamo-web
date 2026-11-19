Remplace en partie : 2026-11-19-contrat-regles-informations.md (plus d'âge minimum propre à une offre).

# `2026-11-19` — Âge minimum : celui de l'application, plus celui de l'offre

- **Type** : décision
- **Question posée** : « utiliser l'âge minimum de l'app et enlever celle de l'offre ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - le champ `min_age` disparaît du logement (étape « Règles » de l'app, onglet « Règles » du back-office) ;
  - l'âge minimum réglé au back-office (« Application », décision 2026-11-19-age-minimum) s'affiche en tête des « Règles » de la fiche de toute offre (« Âge minimum — 21 ans ») ; non réglé, rien.
- **Motif** : la demande de l'humain.
- **Portée** : `RentalDomain`, `OfferDetailResource`, textes `offer::rental.rules`, OpenAPI ; rien dans l'app.
- **Corollaires** : réglé, l'âge minimum fait apparaître la section « Règles » sur toutes les fiches ; un âge déjà enregistré sur une offre reste dans `offers.details` mais n'est plus servi.
- **Tranché par** : humain (demande) ; affichage dans « Règles » par défaut de l'agent
