# `2026-11-24` — Logo d'un partenaire : `list` à 360 px, `detail` à 720 px ; la page sans bordure

Remplace en partie : 2026-11-24-partenaire-logo-original.md, 2026-11-24-partenaire-logo-sans-original.md, 2026-11-24-partenaires-logos-sans-cadre.md

- **Type** : décision
- **Question posée** : « inverser les valeurs des conversions list et detail ; le logo dans détail partenaire utilise la conversion detail et sans bordure », puis « régénère les logos des partenaires ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `PARTNER_LOGO_LIST_SIZE` = 360 (la page « Partenaires » et « Mes partenaires », le logo à gauche en 72 points) et `PARTNER_LOGO_DETAIL_SIZE` = 720 (la page d'un partenaire, déjà servie par sa conversion `detail`) ; valeurs par défaut de la configuration aussi ;
  - `detail` devient la plus grande conversion : le logo enregistré et le minimum à l'envoi restent à 720 px ;
  - la page d'un partenaire affiche son logo sans cadre (`framed={false}`), coins arrondis : plus aucun écran ne garde le cadre ;
  - conversions des logos régénérées en local (`media-library:regenerate --force`).
- **Motif** : la demande de l'humain.
- **Portée** : configuration du module Partner, `.env.example`, `PartnerDetailScreen` de l'app ; tests des conversions et de la page.
- **Corollaires** : aucun.
- **Tranché par** : humain
