# `2026-11-24` — Page « Partenaires » : les logos sans bordure

- **Type** : décision
- **Question posée** : « enlever les bordures sur les logos dans partenaires ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : sur la page « Partenaires », le logo n'a plus de cadre (ni bordure, ni marge) ; il garde la même place (les textes restent alignés) et la remplit. `PartnerLogo` reçoit l'option `framed={false}` ; l'accueil et la page d'un partenaire gardent leur cadre.
- **Motif** : la carte a déjà sa bordure, comme pour « Mes partenaires » (décision 2026-11-24-logo-entier-sans-cadre).
- **Portée** : `PartnerLogo`, `PartnersScreen` de l'app.
- **Corollaires** : sans logo, le nom s'affiche sans cadre sur cette page.
- **Tranché par** : humain
