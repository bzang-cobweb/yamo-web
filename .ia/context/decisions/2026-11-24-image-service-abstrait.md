# `2026-11-24` — Services d'image : une classe abstraite qui lit la configuration

- **Type** : décision
- **Question posée** : « faire une classe abstraite que OfferPhotoService étend avec toutes les méthodes qui lisent la config. le but c'est que PartnerLogoService étende la même classe ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `App\Support\Media\ConfiguredImageService`, abstraite : chaque service d'image ne déclare que sa clé de configuration (`configKey()` : `offer.photos`, `partner.logo`) ; la classe lit `acceptedMimeTypes()`, `maxUploadKb()`, `maxFiles()` (1 sans réglage), `quality()`, `conversions()`, `conversionSize($name)` et `storedSize()` ;
  - `storedSize()` porte les deux choix de la skill `media-conversions` : le plafond de l'original quand `original_size` est réglé (logo d'un partenaire), sinon la plus grande conversion (photos d'une offre) ;
  - `OfferPhotoService` et `PartnerLogoService` l'étendent ; ce qui leur est propre reste chez eux : le cadre d'une photo (`bounds()`), le minimum à l'envoi d'un logo (`minUploadSize()`, qui lit `conversionSize('list')`) ;
  - les modèles `Offer` et `Partner` bouclent sur `conversions()` et lisent `quality()` de leur service, plus la configuration directement.
- **Motif** : un seul domicile pour la lecture des réglages d'une image.
- **Portée** : `ConfiguredImageService`, `OfferPhotoService`, `PartnerLogoService`, modèles `Offer` et `Partner`.
- **Corollaires** : `AvatarService` (photo de profil) lit encore `config('auth.avatar')` lui-même ; il peut étendre la même classe. L'enregistrement (`store()`) reste écrit dans chaque service.
- **Tranché par** : humain
