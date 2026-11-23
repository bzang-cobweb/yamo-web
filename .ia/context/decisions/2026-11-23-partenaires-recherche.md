# `2026-11-23` — Partenaires : la recherche

- **Type** : décision
- **Question posée** : « rajouter une recherche sur la liste des partenaires ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : la page « Partenaires » cherche dans le nom et la description, dans toutes leurs langues, sans casse, avec la règle commune « contient » (`ContainsSearch`, via `PartnerService::search()`) ; la recherche de la liste du back-office utilise la même règle (elle ne trouvait pas les noms accentués).
- **Motif** : la demande de l'humain.
- **Portée** : `PartnerListing`, `PartnerTable`, `PartnerService`.
- **Corollaires** : aucun.
- **Tranché par** : humain
