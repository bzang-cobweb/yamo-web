Remplacée par : 2026-11-27-mes-partenaires-statistiques.md

# `2026-11-27` — « Mes partenaires » : le nombre de membres sur la carte

- **Type** : décision
- **Question posée** : « afficher le nombre de membres dans mes partenaires cards ».
- **Ce qui est retenu** : chaque carte de « Mes partenaires » porte un compteur « N membres » (`partner::partner.member.members_count`, « 1 membre », « 3 membres »), le nombre de ses comptes rattachés (`withCount('users')` dans la requête de la liste). C'est un compteur pour information, sans ton : l'app le montre en pastille vert doux, en bas à droite de la carte, comme « 1 notification non lue » (décision 2026-11-22-compteurs-non-lus-info) ; il ne s'ouvre pas — la page des membres reste dans le menu ⋮ (« Membres »).
- **Portée** : web, `MyPartnerListing` (requête et carte). Aucun changement mobile : la carte sait déjà afficher ces compteurs.
- **Tranché par** : humain
