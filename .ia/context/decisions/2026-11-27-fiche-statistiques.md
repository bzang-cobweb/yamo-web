Remplacée par : 2026-11-27-fiche-sans-vues-membres.md

# `2026-11-27` — Fiche d'un partenaire : « Vues » ouvre « Statistiques », « Membres » ouvre ses membres

- **Type** : décision
- **Question posée** : « ouvrir partner membres dans un panneau avec sa hauteur maximale, faire un composant statistiques et l'ouvrir de la même manière ; ce composant contient le nombre de views et de clicks ».
- **Interprétation retenue** : depuis le tableau de la fiche d'un partenaire, ses lignes « Vues » et « Membres » (décisions 2026-11-27-fiche-ligne-vues et 2026-11-27-fiche-ligne-membres).
- **Ce qui est retenu** :
  - Dans le tableau, la valeur de « Vues » et celle de « Membres » sont des liens (couleur principale). « Membres » ouvre, à taille maximale, le panneau « Membres » (`PartnerMembers` : recherche, nombre, chaque compte, panneau d'un membre). « Vues » ouvre, à taille maximale, le panneau « Statistiques » (`PartnerStatistics`) : une ligne « Vues » et une ligne « Clics », le libellé à gauche, le nombre à droite.
  - `GET /api/v1/partners/{id}` sert, à ses comptes seulement comme ses vues, `clicks` `{count, label}` (« 3 clics »).
  - `PartnerLoader` et `ContactRow` passent dans leurs propres fichiers (la fiche et les membres s'en servent, sans import circulaire).
- **Portée** : web : `PartnerResource`, langues (`member.clicks`, `partners.statistics`, `partners.clicks`) ; mobile : `PartnerDetails`, `PartnerStatistics` (nouveau), `PartnerLoader`, `ContactRow`, type `PartnerCount`. La page « Membres » de « Mes partenaires » reste une page.
- **Tranché par** : humain
