# `2026-11-19` — Tous les avis d'une offre (mode nuit)

- **Type** : décision
- **Question posée** : demande « offre détail voir les avis », traitée en mode nuit sans humain.
- **Réponse** : aucune (mode nuit) ; lecture retenue : la liste complète des avis, hors périmètre du contrat des avis.
- **Ce qui est retenu** :
  - `GET /api/v1/offers/{offer}/reviews` : ouvert sans connexion, mêmes règles d'accès que la fiche, offre publiée seulement (sinon 404, même pour le propriétaire) ; du plus récent au plus ancien, 20 par page, curseur ;
  - une seule forme d'avis (`OfferReviewResource`), aussi pour les 5 derniers de la fiche ;
  - l'app affiche « Voir les N avis » sur la fiche quand il y en a plus que ceux affichés, qui ouvre l'écran `/offers/[id]/reviews`.
- **Motif** : la fiche n'en montre que 5 ; la liste complète était explicitement reportée (décision 2026-11-19-contrat-avis-offres, corollaires).
- **Portée** : module Offer (route, contrôleur, ressources) ; app (fiche, écran des avis).
- **Corollaires** : pas de tri par note ni de filtre ; le lien n'apparaît pas quand tous les avis sont déjà sur la fiche.
- **Tranché par** : session de nuit
