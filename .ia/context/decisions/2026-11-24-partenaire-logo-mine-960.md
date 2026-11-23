# `2026-11-24` — Logo d'un partenaire : la conversion `mine` à 960 px

Remplace en partie : 2026-11-24-partenaire-logo-conversions.md

- **Type** : décision
- **Question posée** : le logo de Déménageurs du Wouri, en 480 × 480, « n'occupe pas toute la place » sur la carte de « Mes partenaires » ; quelle conversion pour un logo affiché en entier ? → « passe mine à 960 et régénère les logos ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : `PARTNER_LOGO_MINE_SIZE` = 960 (valeur par défaut de la configuration aussi) : la zone 16:9 d'une carte fait 322 points de large, soit 966 px à trois fois ; un logo en bandeau y est net. Le logo enregistré suit (la plus grande conversion, décision 2026-11-24-partenaire-logo-taille-unique) : 960 px au plus.
- **Motif** : la place occupée vient de la forme de la zone (16:9) et de l'affichage en entier (`contain`), pas des pixels ; 480 px rendaient flou un logo en bandeau, agrandi deux fois.
- **Portée** : configuration du module Partner, `.env.example` ; tests des conversions et du back-office.
- **Corollaires** : un logo déjà enregistré à 480 px n'est pas agrandi : les logos de démo ont été recréés (local) ; un logo envoyé plus petit que 960 px reste à sa taille.
- **Tranché par** : humain
