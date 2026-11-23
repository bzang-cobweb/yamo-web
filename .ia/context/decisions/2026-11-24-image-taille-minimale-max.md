# `2026-11-24` — Taille minimale à l'envoi : la plus grande conversion, sans réglage

Remplace : 2026-11-24-image-taille-minimale-commune.md

- **Type** : décision
- **Question posée** : « min_upload ou alors parcourir les conversions pour rechercher le max ? » → « enlever min_upload et utiliser minUploadSize ».
- **Réponse** : plus de réglage `min_upload` ; `minUploadSize()` rend la plus grande conversion.
- **Ce qui est retenu** :
  - `ConfiguredImageService::minUploadSize()` = `largestConversion()`, la plus grande des conversions ; 0 sans conversion (aucun minimum) ; `uploadRules()` rend `MinImageSide` avec ce minimum, toujours sur le plus grand côté ;
  - `PARTNER_LOGO_MIN_UPLOAD`, `OFFER_PHOTOS_MIN_UPLOAD` et `AUTH_AVATAR_MIN_UPLOAD` retirés (configurations, `.env`, `.env.example`) ;
  - minimums : logo 720 px (`list`, inchangé ; le plafond de l'original, 1024, n'y entre pas), photo de profil 288 px (inchangé), photos d'une offre **1920 px** (`web`, au lieu de 1200) ; l'aide des photos l'affiche ;
  - `storedSize()` (sans original gardé) et `minUploadSize()` partagent `largestConversion()`.
- **Motif** : aucune conversion n'est jamais un agrandissement ; aucun réglage à tenir ; toute nouvelle image est protégée.
- **Portée** : `ConfiguredImageService`, configurations des modules Auth, Offer et Partner, `.env.example` ; tests de l'envoi et de la classe.
- **Corollaires** : une photo d'offre de moins de 1920 px est refusée (photo transférée par WhatsApp, capture d'écran, ancien téléphone) ; changer la plus grande conversion d'une image change son minimum.
- **Tranché par** : humain
