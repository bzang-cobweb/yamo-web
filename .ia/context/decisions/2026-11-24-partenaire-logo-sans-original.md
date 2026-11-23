Remplacée en partie par : 2026-11-24-avatar-list-detail.md

# `2026-11-24` — Logo : plus d'original gardé ; photo de profil : original gardé, plafonné à 1024 px

Remplace en partie : 2026-11-24-partenaire-logo-original.md, 2026-11-24-avatar-photos-offre-conversions.md

- **Type** : décision
- **Question posée** : « remove original_size for partner and avatar », puis « no keep original_size for avatar ».
- **Réponse** : le logo n'a plus d'original gardé ; la photo de profil, qui n'en avait pas, en reçoit un.
- **Ce qui est retenu** :
  - logo d'un partenaire : `PARTNER_LOGO_ORIGINAL_SIZE` retiré ; le logo enregistré a la taille de la plus grande conversion (`list`, 720 px), comme les photos d'une offre ;
  - photo de profil : `AUTH_AVATAR_ORIGINAL_SIZE` = 1024 (`config('auth.avatar.original_size')`) ; la photo envoyée est gardée, recadrée en carré, plafonnée à 1024 px ; ses conversions (288 au plus) en sont tirées et peuvent grandir jusque-là sans redemander les photos ;
  - recadrer ne doit jamais agrandir : avec `Fit::Crop`, `ConfiguredImageService::store()` réduit le cadre à ce que l'image couvre (une photo de 400 × 300 donne un carré de 300) ;
  - le choix « original plafonné » reste porté par `ConfiguredImageService::storedSize()` (réglage `original_size` facultatif).
- **Motif** : la demande de l'humain.
- **Portée** : configurations des modules Partner et Auth, `.env.example`, `ConfiguredImageService::store()`, `AvatarService` ; tests du logo et de la photo de profil.
- **Corollaires** : les logos déjà enregistrés gardent leur taille (jusqu'à 1024 px, en local) ; les photos de profil enregistrées avant restent à 288 px.
- **Tranché par** : humain (agent : 1024 px pour l'original de la photo de profil, et le cadre réduit au recadrage)
