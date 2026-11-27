# `2026-11-27` — « Mes partenaires » : « Membres » s'ouvre dans un panneau

Remplace en partie : 2026-11-27-mes-partenaires-membres.md (la page `/partners/{id}/members`)

- **Type** : décision
- **Question posée** : « ouvrir membres dans un panneau ».
- **Ce qui est retenu** : « Membres », dans le menu d'une carte de « Mes partenaires », ouvre le panneau `partner.members` (décision 2026-11-27-action-panneau), à taille maximale, titré « Membres » : `PartnerMembers` (recherche, nombre, chaque compte, panneau d'un membre). La page `/partners/{id}/members` et `PartnerMembersScreen` disparaissent ; la route `partners/[id]` redevient un fichier.
- **Portée** : web : `MyPartnerListing` ; mobile : `ActionPanel`, route et écran retirés.
- **Tranché par** : humain
