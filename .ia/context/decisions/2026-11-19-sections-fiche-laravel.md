Remplace : 2026-11-19-premiere-section-ouverte.md

# `2026-11-19` — Fiche d'une offre : l'ordre et l'ouverture des sections sont définis dans Laravel

- **Type** : décision
- **Question posée** : « open accordéon est défini dans laravel ? » ; j'ai répondu que non (l'app le décidait) et proposé de le servir ; réponse : « oui, défini dans laravel ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - la configuration `offer.sections` (module Offer) liste les sections repliables de la fiche dans l'ordre, avec leur état à l'arrivée : `location` ouverte, `facts`, `rules`, `reviews` fermées ;
  - la fiche sert `sections` : `[{key, open}]`, seulement les sections qui ont un contenu ; si aucune des sections servies n'est ouverte, la première l'est ;
  - l'app affiche les sections dans cet ordre, ouvertes ou non selon `open`, et ignore une clé inconnue. Les titres restent des textes de l'app.
- **Motif** : demande de l'humain ; changer l'ordre ou l'ouverture sans nouvelle version de l'app.
- **Portée** : `Modules/Offer/config/config.php`, `OfferDetailResource` ; `OfferDetailScreen` (mobile).
- **Corollaires** : une section absente de la configuration n'est plus affichée ; le message d'un avis donné ou retiré s'affiche désormais après toutes les sections (et non entre les règles et les avis).
- **Tranché par** : humain
