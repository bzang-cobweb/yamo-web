# `2026-11-24` — Logo d'un partenaire : une conversion par écran

- **Type** : décision
- **Question posée** : « définir les conversions pour les logos (home, partenaires, mes partenaires et détail partenaire) dans la config ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - quatre conversions Spatie, déclarées dans `config('partner.logo.conversions')` et lues dans le `.env` : `home` (carrousel de l'accueil, `PARTNER_LOGO_HOME_SIZE`, 288 px), `list` (page « Partenaires », `PARTNER_LOGO_LIST_SIZE`, 168 px), `mine` (« Mes partenaires », `PARTNER_LOGO_MINE_SIZE`, 480 px), `detail` (page d'un partenaire, `PARTNER_LOGO_DETAIL_SIZE`, 360 px) ; le plus grand côté, trois fois l'affichage, proportions gardées, en WebP à la qualité du logo, produites tout de suite (sans file d'attente) ;
  - chaque écran sert la sienne (`Partner::logoUrl($conversion)`) : le carrousel et la page par l'API des partenaires, les deux listes par leur carte ; tant qu'une conversion n'est pas produite, le logo lui-même ;
  - le logo enregistré reste optimisé (`PARTNER_LOGO_STORED_SIZE`, décision 2026-11-24-partenaire-logo-optimise) ; les conversions en sont tirées.
- **Motif** : la demande de l'humain ; chaque écran télécharge le logo à sa taille.
- **Portée** : modèle `Partner`, configuration du module Partner, `PartnerResource`, `PartnerListing`, `MyPartnerListing` ; `.env.example`.
- **Corollaires** : changer une taille demande de régénérer les conversions (`php artisan media-library:regenerate "Modules\Partner\Models\Partner"`) ; fait en local.
- **Tranché par** : humain
