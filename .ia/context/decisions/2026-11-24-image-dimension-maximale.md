# `2026-11-24` — Images envoyées : un maximum réglable, 10 000 px

- **Type** : décision
- **Question posée** : le garde-fou `dimensions:max_width=10000,max_height=10000` d'`AvatarService` → « Prévoir une config pour le max autrement mettre 1920 » ; 1920 refuserait les photos de téléphone (3000 à 4000 px) → « 10000 px ».
- **Réponse** : un réglage par image, 10 000 px par défaut.
- **Ce qui est retenu** :
  - `max_dimension` dans la configuration de chaque image (`AUTH_AVATAR_MAX_DIMENSION`, `OFFER_PHOTOS_MAX_DIMENSION`, `PARTNER_LOGO_MAX_DIMENSION`, 10 000), lu par `ConfiguredImageService::maxDimension()` (10 000 sans réglage) ;
  - `uploadRules()` le joint toujours au minimum, dans la même règle `dimensions` (photo de profil, photos d'une offre) ou à côté de `MinImageSide` (logo) : l'app, le back-office (où il manquait pour le logo et les photos) et l'envoi de la photo de profil l'ont ;
  - la copie d'`AvatarService::rules()` est retirée ; celle de `Field::fileRules()` ne vaut plus que pour un champ d'images sans règles de service (aucun aujourd'hui).
- **Motif** : un garde-fou contre une image démesurée à décoder (une image d'une couleur de 30 000 × 30 000 pèse peu mais occupe des gigaoctets décodée), pas une taille d'affichage : l'image est réduite à l'enregistrement.
- **Portée** : `ConfiguredImageService`, `AvatarService`, `Field`, configurations des modules Auth, Offer et Partner, `.env.example` ; tests.
- **Corollaires** : aucun.
- **Tranché par** : humain
