Remplacée en partie par : 2026-11-24-partenaire-logo-taille-unique.md
# `2026-11-24` — Logo d'un partenaire : optimisé aussi depuis le back-office, réglé par la configuration

- **Type** : décision
- **Question posée** : « l'url du logo passé à l'api est bien en fonction de la conversion ? si oui quelles sont les conversions pour le logo ? » → « oui, optimise », puis « dans PartnerLogoService toutes les constantes excepté logo doivent être déclarées dans la config et lues dans le .env ».
- **Réponse** : pas de conversion Spatie ; le logo est optimisé à l'enregistrement, désormais aussi depuis le back-office.
- **Ce qui est retenu** :
  - un seul chemin d'enregistrement, `PartnerLogoService::store()` : WebP, 512 px au plus sur le grand côté, proportions gardées, l'original n'est pas conservé ; l'app (fichier en attente du formulaire) et le back-office (`saveUploadedFileUsing`, comme la photo de profil) l'empruntent ; l'URL servie est celle de ce fichier ;
  - les réglages sont dans `config('partner.logo')`, lus dans le `.env` : `PARTNER_LOGO_MIME_TYPES` (liste séparée par des virgules), `PARTNER_LOGO_MAX_UPLOAD_KB`, `PARTNER_LOGO_STORED_SIZE`, `PARTNER_LOGO_QUALITY` ; seul le nom du champ (`logo`) reste une constante ; variables ajoutées à `.env.example` et au `.env` local.
- **Motif** : un logo envoyé au back-office était gardé tel quel et pouvait être lourd à chaque affichage de l'accueil.
- **Portée** : `PartnerLogoService`, `PartnerForm` (app et back-office), configuration du module Partner.
- **Corollaires** : les logos déjà enregistrés depuis le back-office ne sont pas réencodés ; ils le seront au prochain envoi.
- **Tranché par** : humain
