Remplace en partie : 2026-11-23-partenaires-logos.md (le toucher sur un logo).

# `2026-11-23` — Partenaires : une page de liste et une page par partenaire

- **Type** : décision
- **Question posée** : « faire une page de liste et de détail sur les partenaires », puis « garder le infinite scroll comme sur toutes les listes ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - page « Partenaires » (`/partners`) : la liste générique `partner.all` (`GET /api/v1/lists/partner.all`, sans compte), comme toutes les listes : recherche, défilement infini, tirer pour recharger ; tous les partenaires visibles, dans l'ordre du back-office ; chacun avec son logo, son nom et le début de sa description ;
  - page d'un partenaire (`/partners/{id}`, `GET /api/v1/partners/{id}`, 404 s'il n'est pas visible) : son logo, son nom, sa description ; en bas, son bouton (son libellé, sinon « Visiter le site »), qui compte un clic et ouvre son lien ; sans lien, pas de bouton ;
  - sur l'accueil : une flèche à côté de « Partenaires » ouvre la page « Partenaires » ; toucher un logo ouvre la page du partenaire (et non plus son lien directement) ;
  - les vues ne se comptent qu'à l'accueil ; ni la liste ni la page n'en comptent.
- **Motif** : les demandes de l'humain.
- **Portée** : module `Partner` (`PartnerListing`, `PartnerController::show`) ; `features/partner` de l'app.
- **Corollaires** : un clic ne se compte plus qu'au bouton de la page du partenaire.
- **Tranché par** : humain
