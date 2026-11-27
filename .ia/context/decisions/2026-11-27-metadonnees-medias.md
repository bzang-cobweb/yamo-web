# `2026-11-27` — Gestionnaire de médias : métadonnées d'un fichier

- **Type** : décision
- **Question posée** : « ajouter la possibilité de gérer les metadata du media » : quels champs, par langue ou non, et pour les médias des contenus ?
- **Réponse** :
  - champs modifiables : texte alternatif, titre et légende **par langue** (fr, en, de), crédit / copyright, paires clé/valeur libres ;
  - les médias des contenus (photos d'offres, logos de partenaires, avatars…) ont aussi leurs métadonnées modifiables (choix d'abord « lecture seule », changé par l'humain le même jour) ; leurs fichiers restent en lecture seule (pas de renommage, déplacement ni suppression) ;
  - une action « Informations » sur chaque fichier montre aussi ce que dit le fichier : type, taille, dimensions d'une image, dates, et pour un média des contenus ce à quoi il est rattaché.
- **Motif** : l'accessibilité et le référencement des images (texte alternatif) dans chaque langue de l'application, photos d'offres comprises ; les fichiers des contenus restent gérés par leurs écrans.
- **Portée** : paquet `bza/laravel-media-manager`, fichiers du gestionnaire. Ne change pas l'API de l'app ni l'usage des médias des contenus.
- **Corollaires** (orchestrateur) :
  - rangées dans les `custom_properties` du média sous la clé `metadata` (les autres propriétés sont gardées) ; un texte ou une paire vide n'est pas gardé ; tout vider retire la clé ;
  - modifier demande le droit « Modifier » sur le dossier (pour un média des contenus, sur le dossier de son type de modèle, où « Modifier » ne vaut que pour les métadonnées) ; « Voir » suffit pour consulter ;
  - les textes saisis sur une photo d'offre ne servent qu'une fois lus par l'app ou les écrans des offres : rien ne les lit encore ;
  - le crédit n'est pas traduit ; une clé libre fait 100 caractères au plus, un texte 1000 ;
  - `MediaMetadata::text()` lit un texte dans une langue, sinon la langue de repli, sinon le premier rempli ;
  - les dimensions ne sont lues que sur un disque local.
- **Tranché par** : humain (champs, langues, médias des contenus) ; orchestrateur (corollaires)
