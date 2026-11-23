# `2026-11-24` — Photo de profil et photos d'une offre : réglages dans le `.env`, une conversion par écran

Remplace en partie : 2026-11-16-contrat-photo-de-profil.md, 2026-11-19-contrat-champ-upload-photos.md

- **Type** : décision
- **Question posée** : « applique maintenant la même règle pour l'avatar de l'utilisateur et aussi pour les images de l'offre mais en plus grand pour l'offre », puis « quelle taille pour les photos des offres ? penser aussi qu'elles serviront le siteweb ».
- **Réponse** : la demande elle-même ; pour la taille, « 1920 px avec conversion web » (recommandation retenue).
- **Ce qui est retenu** — la règle du logo d'un partenaire (décisions 2026-11-24-partenaire-logo-config, -conversions, -taille-unique ; skill `bza-laravel:media-conversions`) :
  - réglages lus dans le `.env` par la configuration du module, plus aucune constante de taille, de type, de poids ou de qualité dans le code : `AUTH_AVATAR_*` (`config('auth.avatar')`) et `OFFER_PHOTOS_*` (`config('offer.photos')`, nombre maximal de photos compris) ;
  - photo de profil : un carré recadré au centre ; conversions `profile` (profil, 96 points, 288 px), `member` (page d'un membre, 288 px), `actor` (qui agit dans une notification, 144 px ; l'app ne l'affiche pas encore), `admin` (back-office, 256 px) ; la photo enregistrée est un carré de la plus grande (288 px, au lieu de 512 px) ;
  - photos d'une offre : bornées par la largeur, les cadres de l'app étant en 16:9 (hauteur au plus quatre fois la largeur) ; conversions `web` (site web : galerie de la fiche et agrandissement, 1920 px), `detail` (carrousel de la fiche, 1200 px), `list` (cartes des listes : recherche, mes logements, favoris, demandes ; 1080 px), `home` (carrousels de l'accueil, 540 px), `form` (vignettes du champ photos, 320 px) ; la photo enregistrée prend la plus grande (1920 px de large, au lieu de 1600 px sur le grand côté) ;
  - chaque écran demande la sienne (`User::avatarUrl($conversion)`, `Offer::photoUrl($conversion)`, `Offer::photoUrls($conversion)`), avec repli sur l'image enregistrée tant que la conversion n'est pas produite ;
  - le back-office et les données de démo enregistrent par le même service que l'app (`AvatarService::store`, `OfferPhotoService::store`) : l'envoi de photos d'une offre depuis le back-office gardait jusqu'ici l'original sans l'optimiser.
- **Motif** : la demande de l'humain ; chaque écran télécharge l'image à sa taille. L'original n'étant pas gardé, la photo enregistrée borne ce que le site web pourra afficher : 1920 px couvrent un écran Full HD.
- **Portée** : modules Auth (configuration, `User`, `AvatarService`, `UserResource`, Filament), Offer (configuration, `Offer`, `OfferPhotoService`, `OfferDetailResource`, listes, formulaires app et Filament, `MemberProfile`), Notification (`ActorData`), `DemoSeeder` ; `.env.example`.
- **Corollaires** : changer une taille demande de régénérer (`php artisan media-library:regenerate "Modules\Auth\Models\User" --force`, idem `Modules\Offer\Models\Offer`) ; fait en local. Les photos déjà enregistrées restent à leur taille : une photo de démo de 1200 px n'est pas agrandie pour la conversion `web`. Les écrans du site web auront chacun leur conversion quand ils existeront.
- **Tranché par** : humain
