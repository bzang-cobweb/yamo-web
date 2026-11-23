Remplacée par : 2026-11-24-partenaire-logo-original.md

# `2026-11-24` — Logo d'un partenaire : carré, la conversion `list` à 608 px sert aussi « Mes partenaires »

Remplace : 2026-11-24-partenaire-logo-mine-960.md
Remplace en partie : 2026-11-24-partenaire-logo-conversions.md, 2026-11-24-logo-taille-minimale.md

- **Type** : décision
- **Question posée** : « je vais plutôt opter pour un logo carré et enlever la conversion mine pour utiliser la conversion list sur mes partenaires. Quel est le px qu'il faut fixer ? » → « oui, passe list à 608 et régénère les logos ».
- **Réponse** : 608 px (recommandation retenue).
- **Ce qui est retenu** :
  - plus de conversion `mine` ; « Mes partenaires » sert la conversion `list`, comme la page « Partenaires » ;
  - `PARTNER_LOGO_LIST_SIZE` = 608 (valeur par défaut de la configuration aussi) : un logo carré prend la hauteur de la zone 16:9 d'une carte, 203 points sur le plus grand iPhone (440 points de large), soit 608 px à trois fois ;
  - le format attendu est un logo carré ; il n'est pas imposé à l'envoi ;
  - la plus grande conversion devient `list` : le logo enregistré et le minimum à l'envoi passent de 960 à 608 px (décisions 2026-11-24-partenaire-logo-taille-unique et 2026-11-24-logo-taille-minimale, qui les en déduisent).
- **Motif** : un logo carré remplit les tuiles carrées de l'accueil et de la page « Partenaires » ; une conversion de moins.
- **Portée** : configuration du module Partner, `.env.example` (`PARTNER_LOGO_MINE_SIZE` retiré), `MyPartnerListing` ; tests des conversions, du back-office et de l'envoi.
- **Corollaires** : la page « Partenaires » télécharge un logo de 608 px pour 56 points ; sur un Android plus dense que trois fois (3,5×, grands écrans), le logo d'une carte de « Mes partenaires » est très légèrement agrandi. Retirer une conversion de la configuration laisse ses fichiers sur le disque quand le média est remplacé : les fichiers `-mine.webp` ont été retirés en local ; en production, les retirer à la main avant de déployer (ou régénérer avec `--force` avant de retirer la conversion). Logos de démo recréés en carré (local).
- **Tranché par** : humain
