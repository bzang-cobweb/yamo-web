# `2026-11-24` — Cartes « Partenaires » et « Mes partenaires » : sans logo, l'emplacement reste vide

- **Type** : décision
- **Question posée** : « partenaires et mes partenaires cards : laisser vide si pas de logo ».
- **Ce qui est retenu** :
  - **« Partenaires »** : sans logo, l'emplacement du logo reste vide. Avant, il montrait le nom du partenaire (2026-11-23-partenaires-logos). `PartnerLogo` reçoit `named={false}`.
  - **« Mes partenaires »** : l'emplacement était déjà vide (`side-empty`, carte de liste générique) ; inchangé.
  - **Le carrousel de l'accueil** garde le nom, faute de logo : ce n'est pas une de ces cartes.
- **Tranché par** : humain (la demande) ; session (l'accueil inchangé)
