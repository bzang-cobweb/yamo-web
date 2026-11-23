Remplace en partie : 2026-11-23-module-partenaires.md (ordre des partenaires) ; 2026-11-23-accueil-carrousels-par-ville.md (10 offres fixes).

# `2026-11-23` — Accueil : nombres réglables, partenaires au hasard

- **Type** : décision
- **Question posée** : « faire une requête aléatoire pour afficher 10 partenaires (nombre à définir dans application pareil pour le carousel des offres) ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - page « Application » du back-office, section « Accueil de l'app » : « Offres par ville » et « Partenaires affichés », de 1 à 50 ; vides, 10 (`Setting::homeOffersPerCity()`, `Setting::homePartners()`) ;
  - les carrousels de villes prennent ce nombre d'offres au hasard ;
  - les partenaires visibles sont tirés au hasard, autant que réglé ; seuls ceux servis comptent une vue.
- **Motif** : la demande de l'humain.
- **Portée** : `Setting`, `SettingResource`, `OfferBrowseListing::cityCarousels()`, `PartnerService::shown()`.
- **Corollaires** : le champ « Ordre » d'un partenaire ne change plus l'affichage dans l'app.
- **Tranché par** : humain
