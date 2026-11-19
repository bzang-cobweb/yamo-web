Remplacée par : 2026-11-19-sections-fiche-laravel.md (l'ordre et l'ouverture viennent de Laravel)

Remplace en partie : 2026-11-19-fiche-localisation-accordeon.md (les trois sections étaient ouvertes à l'affichage).

# `2026-11-19` — Fiche d'une offre : seule la première section repliable est ouverte

- **Type** : décision
- **Question posée** : demande directe de l'humain : « fermer tous les accordéons exceptés le premier. où est-ce qu'on le défini ? ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - à l'arrivée sur la fiche, seule la première section affichée est ouverte ; les autres sont fermées et s'ouvrent au toucher de leur titre ;
  - la première est « Localisation » si l'offre a une adresse, sinon « Caractéristiques », sinon « Règles », sinon « Avis » ;
  - l'état de départ se règle par `initiallyOpen` du composant `Accordion` ; la fiche le calcule (`firstSection()` dans `OfferDetailScreen`).
- **Motif** : demande de l'humain.
- **Portée** : `OfferDetailScreen` (mobile).
- **Corollaires** : l'ouverture n'est pas gardée d'une fiche à l'autre.
- **Tranché par** : humain
