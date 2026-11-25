# `2026-11-25` — Un clic à chaque ouverture de la fiche, d'où qu'on vienne

- **Type** : décision
- **Question posée** : « clic augmente quand on clique sur tous liens qui mènent au détail partenaire ou offres (sauf propriétaire ou partner_user) » ; puis « le bloc statistiques sur les offres comme sur les partenaires ».
- **Ce qui est retenu** :
  - **Clic** : l'app compte un clic à l'ouverture de la fiche d'une offre ou de la page d'un partenaire, une fois par ouverture, d'où qu'on vienne :
    - accueil, recherche, liste « Partenaires », favoris ;
    - demandes, notifications ;
    - lien direct.
  - Elle appelle `POST /api/v1/offers/{id}/click` ou `POST /api/v1/partners/{id}/click` à l'ouverture. Les cartes n'appellent plus rien elles-mêmes.
  - Un rechargement de la fiche ne compte pas de nouveau clic, un retour sur une fiche restée ouverte non plus.
  - Le propriétaire d'une offre et les comptes d'un partenaire (`partner_user`) ne comptent rien.
  - Une offre que les autres ne voient pas répond 404, et l'app l'ignore.
  - **Statistiques** : au back-office, la fiche d'une offre a un onglet « Statistiques » avec « Vues » et « Clics », en lecture seule, comme un partenaire.
  - Les vues ne changent pas (décisions 2026-11-25-compteurs-offres et 2026-11-25-compteurs-partenaires).
- **Remplace** : le clic au toucher d'une carte de l'accueil, de la recherche ou de la page « Partenaires » seulement.
- **Tranché par** : humain
