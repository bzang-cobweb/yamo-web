# `2026-11-27` — « Mes partenaires » : plus de pastilles ; « Statistiques » dans le menu

Remplace : 2026-11-27-mes-partenaires-nombre-membres.md et 2026-11-27-mes-partenaires-vues-en-pastille.md

- **Type** : décision
- **Question posée** : « enlever les tags vues et membres sur les cards ; ajouter un lien statistique dans le menu qui ouvre un panneau avec le tableau vues et clics ».
- **Ce qui est retenu** : les cartes de « Mes partenaires » n'ont plus de pastilles « 12 vues » ni « 3 membres » (ni œil) ; leur menu ⋮ a « Statistiques » (`partner::partner.member.statistics`), après « Membres », qui ouvre le panneau `partner.statistics` (décision 2026-11-27-action-panneau) : le tableau des vues et des clics du partenaire (`PartnerStatistics`, le même que sa fiche), lu par `GET /api/v1/partners/{id}`.
- **Portée** : web : `MyPartnerListing` (plus de `withCount('users')` ni de compteurs) ; mobile : `PartnerStatisticsPanel`.
- **Tranché par** : humain
