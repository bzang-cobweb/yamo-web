# `2026-11-24` — Page « Partenaires » et accueil : les logos sans bordure

- **Type** : décision
- **Question posée** : « enlever les bordures sur les logos dans partenaires », puis « enlever aussi les bordures sur les logos de l'accueil ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : sur la page « Partenaires », le logo n'a plus de cadre (ni bordure, ni marge) ; il garde la même place (les textes restent alignés) et la remplit. `PartnerLogo` reçoit l'option `framed={false}` ; le carrousel de l'accueil l'utilise aussi ; seule la page d'un partenaire garde son cadre.
- **Motif** : la carte a déjà sa bordure, comme pour « Mes partenaires » (décision 2026-11-24-logo-entier-sans-cadre).
- **Portée** : `PartnerLogo`, `PartnersScreen`, `PartnersStrip` de l'app.
- **Corollaires** : sans logo, le nom s'affiche sans cadre sur ces deux écrans.
- **Tranché par** : humain
