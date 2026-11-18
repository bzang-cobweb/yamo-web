Remplacée par : 2026-11-19-item-titre-saisi-details.md (pour le contenu de `highlight` des offres seulement)

# `2026-11-19` — Contrat des avis sur les offres, validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-avis-offres.md`.
- **Réponse** : « Approved ».
- **Ce qui est retenu** :
  - avis = note entière 1–5 et commentaire obligatoire (≤ 1 000 caractères, non traduit) ;
  - réservé à un membre connecté, sur une offre publiée du pays courant qui n'est pas la sienne ; un avis par membre et par offre, modifiable et supprimable ; publication immédiate, modération a posteriori (suppression) dans le back-office ;
  - `ListItem` générique gagne `rating` `{average, count}` et `highlight` ; pour les offres, `highlight` = dernier commentaire (140 caractères), dans `offer.browse` et `offer.mine` ;
  - la fiche sert un bloc `reviews` (moyenne, nombre, 5 derniers, `mine`, `can_review`) ; on donne son avis par le formulaire serveur `offer.review`, avec un champ générique `rating` (étoiles) ;
  - la moyenne est calculée à la requête, sans colonne dénormalisée.
- **Motif** : demande de l'humain ; précisions « Visitor reviews », « A rating out of 5 stars ».
- **Portée** : module Offer (avis), module Form (`ListItem`, champ `rating`), app mobile (item de liste, fiche, champ étoiles).
- **Corollaires** : hors périmètre — réponse du propriétaire, signalement, avis vérifiés, notifications, liste complète des avis, tri par note.
- **Tranché par** : humain
