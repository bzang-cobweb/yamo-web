# Contrat — Tous les avis, ordre des photos, libellé du champ upload, « Retirer » sous l'image

Validation : non — exécuté en mode nuit le 2026-11-19

Demande : « 1) offre détail voir les avis 2) offre formulaire : changer l'ordre des photos, enlever le label du champ upload, 3) offre ListItem mettre le bouton retirer sur sa ligne afin que l'image occupe toute la ligne ».

## Ce que j'ai compris

1. **Voir les avis** : la fiche ne montre que les 5 derniers avis ; la liste complète
   était hors périmètre du contrat des avis (décision 2026-11-19-contrat-avis-offres,
   corollaires). La fiche gagne un lien « Voir les N avis » quand il y en a plus que
   ceux affichés, qui ouvre un écran avec tous les avis, du plus récent au plus ancien,
   chargés par pages.
2. **Ordre des photos** : dans le champ `upload`, chaque photo peut avancer ou reculer
   d'une place (flèches ‹ ›), au lieu de la seule étoile « Mettre en premier ». La
   première reste la principale.
   **Libellé** : le champ « Photos » n'affiche plus son libellé (le titre de l'étape
   le dit déjà). Réglé dans Laravel : `Field::hideLabel()`, servi `label_hidden`. Le
   libellé reste lu par l'accessibilité et sert aux messages d'erreur.
3. **Retirer sous l'image** : dans les listes du profil (« Mes logements »), un item
   qui a une photo affiche « Retirer » sur sa propre ligne, sous le contenu ; la photo
   prend toute la largeur de la carte. Un item sans photo (adresses) ne change pas.

## Découpage

- Web : `GET /api/v1/offers/{offer}/reviews` (sans connexion, mêmes règles que la
  fiche, avis d'une offre publiée seulement), par curseur, 20 par page ; une seule
  forme d'avis (`OfferReviewResource`), reprise par les 5 derniers de la fiche.
  `Field::hideLabel()` ; `photos` l'utilise.
- App : route `/offers/[id]/reviews` et écran des avis ; lien sur la fiche ;
  `UploadField` (flèches) ; `FieldShell` (libellé masqué) ; `ProfileListScreen`
  (mise en page avec photo).

## Critères de succès (état observable)

- Une offre publiée avec plus de 5 avis : la fiche montre « Voir les N avis » ; l'écran
  ouvert liste tous les avis, page après page. Une offre non publiée : 404.
- Les avis de la fiche et ceux de la liste ont la même forme (auteur prénom, note,
  commentaire, date).
- Au formulaire, une photo peut être déplacée d'une place à gauche ou à droite ;
  l'ordre enregistré est celui affiché.
- Le champ « Photos » n'a plus de libellé visible ; l'erreur « Le champ Photos est
  obligatoire. » reste.
- Dans « Mes logements », la photo occupe toute la largeur et « Retirer » est sur sa
  ligne ; la liste des adresses ne change pas.
- Suites serveur et app vertes, tsc et lint propres, vérification au simulateur, QA
  indépendante.
