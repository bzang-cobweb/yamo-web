# `2026-11-23` — « Mes logements » : Publier / Dépublier dans le menu ⋮

- **Type** : décision
- **Question posée** : « ajouter un lien publier/dépublier lorsque c'est autorisé sur les 3 petits points dans mes logements (statut publié/brouillon) ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - « Publier » sur un brouillon : il passe à « Publiée » (`POST /api/v1/offers/{offer}/publish`, règle `publish`) ;
  - « Dépublier » sur un logement publié : il repasse en brouillon (`POST /api/v1/offers/{offer}/unpublish`, règle `unpublish`), refusé tant qu'une demande est en cours, comme dans le formulaire (décision 2026-11-22-logement-verrouille-par-demandes) ;
  - rien pour une offre archivée : elle se change par le formulaire ;
  - le message de réussite dans le bandeau, la liste rechargée.
- **Motif** : la demande de l'humain.
- **Portée** : `OfferPolicy`, `OfferController`, `OfferService::changeStatus`, `OfferListing`, routes API ; textes fr, en, de.
- **Corollaires** : la liste charge « a une demande en cours » en une requête (`has_open_bookings`), lu par `Offer::hasOpenBookings()`. Aucun changement dans l'app.
- **Tranché par** : humain
