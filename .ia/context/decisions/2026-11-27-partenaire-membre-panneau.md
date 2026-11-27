# `2026-11-27` — Membres d'un partenaire : la liste seule, l'administrateur en vert, un panneau par membre

Remplace en partie : 2026-11-27-partenaire-membres.md (le nom et le slogan en tête, la pastille grise, un membre qui ouvre sa page)

- **Type** : décision
- **Question posée** : « dans membres : enlever le nom et le slogan ; tag administrateur en vert ; le clic sur un membre ouvre un panneau avec sa hauteur adaptée au contenu dans lequel on affiche photo, nom, téléphone, email ».
- **Ce qui est retenu** :
  - Le panneau « Membres » montre le nombre de comptes puis chacun ; plus le nom ni le slogan du partenaire.
  - La pastille « Administrateur » a le ton `success` (vert).
  - Toucher un membre ouvre, par-dessus, un panneau à la hauteur de son contenu, titré de son nom : sa photo, son nom, son téléphone et son e-mail (chacun ouvre l'appel ou l'e-mail ; absent s'il n'en a pas). Plus de page du membre ouverte depuis cette liste.
  - `GET /api/v1/partners/{id}` sert, pour chaque membre et toujours aux seuls comptes du partenaire, `email` et `phone` (format international).
- **Motif** : les comptes d'un même partenaire travaillent ensemble et doivent pouvoir se joindre.
- **Risque assumé** : l'e-mail et le téléphone d'un compte sont visibles des autres comptes du même partenaire.
- **Portée** : web : `PartnerResource::members()` ; mobile : `PartnerMembers`, `ContactRow` exporté de `PartnerDetails`, type `PartnerMember`.
- **Tranché par** : humain
