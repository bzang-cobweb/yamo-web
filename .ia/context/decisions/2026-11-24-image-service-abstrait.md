# `2026-11-24` — Services d'image : une classe abstraite qui lit la configuration et enregistre l'image

- **Type** : décision
- **Question posée** : « faire une classe abstraite que OfferPhotoService étend avec toutes les méthodes qui lisent la config. le but c'est que PartnerLogoService étende la même classe », puis « add store in abstract class and $model param type HasMedia », puis « move storeUpload to ConfiguredImageService / AvatarService étend aussi ConfiguredImageService ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `App\Support\Media\ConfiguredImageService`, abstraite : chaque service d'image ne déclare que sa clé de configuration (`configKey()` : `offer.photos`, `partner.logo`) ; la classe lit `acceptedMimeTypes()`, `maxUploadKb()`, `maxFiles()` (1 sans réglage), `quality()`, `conversions()`, `conversionSize($name)` et `storedSize()` ;
  - `storedSize()` porte les deux choix de la skill `media-conversions` : le plafond de l'original quand `original_size` est réglé (logo d'un partenaire), sinon la plus grande conversion (photos d'une offre) ;
  - `OfferPhotoService` et `PartnerLogoService` l'étendent ; ce qui leur est propre reste chez eux : le cadre d'une photo (`bounds()`), le minimum à l'envoi d'un logo (`minUploadSize()`, qui lit `conversionSize('list')`) ;
  - `store(HasMedia $model, string $sourcePath, ?string $name = null): Media` vit dans la classe abstraite : redressée, en WebP, tenue dans `bounds(storedSize())` (un carré par défaut ; la largeur seule pour une photo), dans la collection du service (`collection()`, abstraite) sous son nom (`mediaName()`, celui de la collection par défaut, « photo » pour une offre) ; `storeUpload(HasMedia, FormUpload)` aussi (un fichier en attente de l'app, retiré ensuite) ; deux points d'extension : `fit()` (`Fit::Max` par défaut, `Fit::Crop` pour la photo de profil) et `fileName()` (l'identifiant de l'envoi ou un uuid ; `avatar.webp` pour la photo de profil) ; les services ne gardent que l'envoi depuis l'app (`sync()`, `current()`) ;
  - `AvatarService` l'étend aussi (`auth.avatar`, collection `avatar`) ; il garde `rules()` et `remove()` ;
  - les modèles `Offer`, `Partner` et `User` bouclent sur `conversions()` et lisent `quality()` de leur service, plus la configuration directement.
- **Motif** : un seul domicile pour la lecture des réglages d'une image et pour son enregistrement.
- **Portée** : `ConfiguredImageService`, `OfferPhotoService`, `PartnerLogoService`, `AvatarService`, modèles `Offer`, `Partner` et `User`.
- **Corollaires** : `ConfiguredImageService` (application) dépend de `FormUpload` (module Form) pour `storeUpload()`.
- **Tranché par** : humain
