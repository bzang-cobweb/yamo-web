Remplace en partie : 2026-11-24-partenaire-logo-optimise.md (taille du logo enregistré).

# `2026-11-24` — Logo d'un partenaire : la taille enregistrée est la plus grande conversion

- **Type** : décision
- **Question posée** : « d'où vient le 512 px ? » → (option 1) « supprimer la taille enregistrée et prendre la plus grande conversion », « pourquoi 480 px ? ».
- **Réponse** : 512 px était repris de la photo de profil, sans raison propre au logo ; 480 px (conversion `mine`) était une estimation.
- **Ce qui est retenu** :
  - plus de `PARTNER_LOGO_STORED_SIZE` : le logo enregistré prend la plus grande des tailles de `config('partner.logo.conversions')` (`PartnerLogoService::storedSize()`), aujourd'hui 480 px ; augmenter une conversion augmente la taille enregistrée ;
  - la conversion `mine` : l'emplacement d'un logo dans une carte de « Mes partenaires » fait environ 322 × 111 points sur un iPhone de 402 points, soit 966 × 333 px en haute densité ; 480 px est un compromis (net pour un logo carré ou modérément large) ; `PARTNER_LOGO_MINE_SIZE` à 960 pour des logos très larges parfaitement nets.
- **Motif** : une seule règle ; la source des conversions n'est jamais plus petite qu'elles.
- **Portée** : `PartnerLogoService`, configuration du module Partner, `.env.example`.
- **Corollaires** : la taille minimale utile d'un logo est la même valeur (480 px sur le plus grand côté avec les réglages actuels) ; elle n'est pas imposée à l'envoi.
- **Tranché par** : humain
