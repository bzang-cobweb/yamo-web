Remplace en partie : 2026-11-19-sections-fiche-laravel.md (les sections ne sont plus de simples clés).

# `2026-11-19` — Fiche : sections construites par Laravel, un seul aiguillage dans l'app

- **Type** : décision
- **Question posée** : « dans OfferDetailScreen offer.sections doit être construit côté Laravel. Les données de type facts, rules doivent se faire dans un seul switch dont une seule clé, pareil pour informations. »
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - chaque section servie est complète : `key`, `type`, `title`, `open` et son contenu ;
  - types : `rows` (lignes « libellé — valeur » : caractéristiques, règles), `text` (un texte et une note : la localisation — adresse et indication —, chaque bloc d'informations), `reviews` (les avis, servis à part dans `reviews`) ;
  - chaque bloc d'informations est une section `text` à lui seul (`information-<id>`), avec l'ouverture réglée pour `informations` ;
  - titres servis par Laravel (`offer::offer.section_titles`, fr, en, de) ; l'app ne fait qu'un `switch` sur le type, un type inconnu est ignoré.
- **Motif** : la demande de l'humain : la fiche se décrit côté serveur.
- **Portée** : `OfferDetailResource::sections()`, textes `offer::offer` ; app (`OfferDetailScreen`, type `OfferSection`).
- **Corollaires** : `facts`, `rules`, `informations`, `address`, `indication` restent servis pour une app plus ancienne ; la localisation et les informations partagent désormais le même rendu (texte, note, séparateur).
- **Tranché par** : humain ; découpage en trois types par l'agent
