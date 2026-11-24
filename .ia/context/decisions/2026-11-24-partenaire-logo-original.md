Remplacée en partie par : 2026-11-24-image-taille-minimale-commune.md, 2026-11-24-partenaire-logo-sans-original.md, 2026-11-24-partenaire-logo-list-detail-inverses.md

# `2026-11-24` — Logo d'un partenaire : l'original gardé (1024 px au plus), `list` à 720 px et minimum à l'envoi

Remplace : 2026-11-24-partenaire-logo-taille-unique.md, 2026-11-24-partenaire-logo-list-608.md
Remplace en partie : 2026-11-24-logo-taille-minimale.md, 2026-11-24-partenaire-logo-optimise.md

- **Type** : décision
- **Question posée** : à quoi servent les conversions si l'image de base est gardée, et pourquoi demander du 1024 si 720 suffit ? → « garde l'original plafonné à 1024 px donc rajouter la variable dans le .env. passer 720px pour la conversion list. change le texte d'aide du logo dans le helper du champ en mettant partner.logo.conversions.list comme minimum. mettre la règle de validation sur list ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - le logo envoyé est gardé à la racine de son dossier, en WebP, plafonné à 1024 px sur son grand côté (`PARTNER_LOGO_ORIGINAL_SIZE`, `config('partner.logo.original_size')`), au lieu d'être réduit à la plus grande conversion ; les conversions en sont tirées et peuvent grandir jusque-là sans redemander les logos ;
  - `PARTNER_LOGO_LIST_SIZE` = 720 : un logo carré dans une carte de « Mes partenaires », net jusqu'aux téléphones les plus denses (207 points × 3,5) ;
  - le minimum à l'envoi est la conversion `list` (`config('partner.logo.conversions.list')`), dans la règle `MinImageSide` (app et back-office) et dans l'aide du champ : « Logo carré, au moins 720 × 720 px. » ; le format carré est demandé, pas imposé ;
  - l'aperçu du champ, dans l'app, montre la conversion `list`, pas l'original.
- **Motif** : pouvoir agrandir une conversion plus tard sans redemander les logos ; aucun écran ne sert l'original.
- **Portée** : configuration du module Partner, `.env.example` ; `PartnerLogoService` (`storedSize()`, `minUploadSize()`, `current()`) ; traductions de l'aide ; tests de l'envoi, du back-office et des conversions.
- **Corollaires** : la skill `media-conversions` (enregistrée = plus grande conversion) ne décrit plus ce choix pour le logo ; un logo enregistré avant reste à sa taille (ceux de démo ont été recréés en local, carrés, de 720 à 2000 px).
- **Tranché par** : humain
