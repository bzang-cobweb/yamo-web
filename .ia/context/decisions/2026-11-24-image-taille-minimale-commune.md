# `2026-11-24` — Taille minimale à l'envoi : commune aux trois images

Remplace en partie : 2026-11-24-logo-taille-minimale.md, 2026-11-24-partenaire-logo-original.md

- **Type** : décision
- **Question posée** : « comment on contrôle le min width maintenant ? » — seul le logo avait un minimum, hors de `ConfiguredImageService` → « Oui, généralise ».
- **Réponse** : la demande elle-même ; les conversions de référence des photos et de l'avatar sont un choix de l'agent (ci-dessous).
- **Ce qui est retenu** :
  - `ConfiguredImageService::minUploadSize()` : la taille de la conversion nommée par le réglage `min_upload` de l'image (`*_MIN_UPLOAD` dans le `.env`) ; 0 sans réglage, aucun minimum ; `uploadRules()` rend la règle `MinImageSide` quand il y a un minimum ;
  - logo d'un partenaire : `PARTNER_LOGO_MIN_UPLOAD=list` (720 px), inchangé ; photos d'une offre : `OFFER_PHOTOS_MIN_UPLOAD=detail` (1200 px, le carrousel de la fiche) ; photo de profil : `AUTH_AVATAR_MIN_UPLOAD=profile` (288 px) ;
  - toujours sur le plus grand côté ; appliqué dans l'app (`Field::minImageSide()`, qui ignore 0), dans le back-office (`->rules(<Service>::uploadRules())`) et dans l'envoi de la photo de profil (`AvatarService::rules()`) ;
  - l'aide affiche le minimum : celle du logo (« Logo carré, au moins 720 × 720 px. ») et celle des photos d'une offre (« … JPEG, PNG ou WebP, au moins 1200 px sur le plus grand côté. »).
- **Motif** : une photo trop petite serait agrandie, donc floue, là où elle s'affiche en grand ; une règle et un domicile pour les trois images.
- **Portée** : `ConfiguredImageService`, `Field`, `PartnerLogoService` (son `minUploadSize()` propre retiré), `AvatarService`, formulaires de l'app et du back-office (offre, partenaire, utilisateur), configurations et `.env.example` des modules Auth, Offer et Partner, traductions de l'aide des photos.
- **Corollaires** : une photo d'offre déjà enregistrée plus petite reste ; changer la conversion de référence change le minimum ; l'app ne vérifie pas les dimensions avant l'envoi (le refus vient du serveur, avec son message).
- **Tranché par** : humain (généralisation) ; agent (`detail` pour les photos, `profile` pour l'avatar)
