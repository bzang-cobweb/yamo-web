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

---

## Ce que j'ai décidé seul

- « Voir les avis » = la liste complète des avis (reportée par le contrat des avis), derrière un lien « Voir les N avis » affiché seulement quand la fiche n'en montre pas tous. Une offre non publiée n'a pas de liste (404), même pour son propriétaire.
- L'ordre des photos se change par des flèches ‹ › (une place à la fois) plutôt que par glisser-déposer : aucune dépendance ajoutée la nuit. L'étoile « Mettre en premier » est retirée ; le badge « Principale » passe en haut à gauche.
- Le libellé masqué est un réglage générique de Laravel (`Field::hideLabel()`), pas une règle propre au type `upload`. La marque d'obligation (*) disparaît avec lui.
- « Retirer sur sa ligne » ne vaut que pour les items avec photo (ou avec « Voir » et « Éditer ») : la liste des adresses ne change pas.
- Demande arrivée en cours de nuit (« boutons voir, éditer et retirer ») : traitée dans le même lot, car elle touche la même ligne d'actions. Toucher la carte ouvre toujours le formulaire.

## Ce qui a été fait

- Web : `GET /api/v1/offers/{offer}/reviews` (20 par page, curseur, `throttle:60,1`), `OfferReviewResource` partagé avec la fiche ; `Field::hideLabel()` / `label_hidden`, utilisé par `photos` ; lien `/offers/{id}` sur les items de « Mes logements » ; textes fr/en.
- App : lien et écran `/offers/[id]/reviews` ; flèches dans `UploadField` ; `FieldShell` respecte `label_hidden` ; `ProfileListScreen` en colonne avec ligne d'actions « Voir · Éditer · Retirer ».
- Preuves : 500 tests serveur, 233 tests app, tsc et lint propres ; simulateur : « Mes logements » (photo pleine largeur, trois actions), « Voir » ouvre la fiche du brouillon, étape Photos sans libellé, déplacement d'une photo enregistré dans l'ordre affiché. QA indépendante : GO, 0 bloquant.

## Ce que je n'ai pas vérifié

- L'écran de tous les avis au simulateur : aucune offre de démonstration n'a plus de 5 avis (couvert par les tests de l'app).
- La lecture au lecteur d'écran et la taille des cibles tactiles sur un vrai téléphone.

## Ce qui reste en suspens

- Tri des avis par dernière modification (comme la fiche) : un avis modifié remonte en tête. À confirmer.
- Flèches (24 px) et « Retirer » sous la cible tactile recommandée de 44 pt.
- Avertissement de l'export OpenAPI sur `OfferReviewPageResource` (« Cannot infer the resource model ») : la ressource enveloppe une page, pas un modèle ; sans effet sur le document.
