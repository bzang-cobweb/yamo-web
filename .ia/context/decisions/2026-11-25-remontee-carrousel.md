# `2026-11-25` — « Remontée jusqu'au » : en tête du carrousel de sa région

- **Type** : décision
- **Question posée** : « je souhaite un autre critère payant si paiement activé pour remonter les offres dans les carousels des régions ».
- **Réponses** :
  - nom : « Remontée jusqu'au » ;
  - ordre : « d'abord, au hasard entre elles » ;
  - sans paiement : « Oui, toujours ».
- **Ce qui est retenu** :
  - **Champ** : `offers.boosted_until`, une date indexée, vide par défaut (pas remontée). Une offre est remontée jusqu'à ce jour inclus (`scopeBoosted`).
  - **Back-office** : le champ est dans l'onglet de l'accès de l'offre, à côté de « En vedette jusqu'au », et la liste des offres a une colonne masquable.
  - **Carrousel de sa région, sur l'accueil** :
    - les offres remontées passent d'abord, tirées au hasard entre elles ;
    - les places restantes sont tirées au hasard parmi les autres offres de la région ;
    - ça vaut avec ou sans paiement des propriétaires.
  - **Différence avec la vedette** : « En vedette jusqu'au » met l'offre dans « À la une » et en tête de la recherche. « Remontée jusqu'au » ne joue que dans le carrousel de sa région.
  - Pas de badge.
- **Pas fait** : la remontée n'a pas encore de tarif ni de paiement dans l'app ; elle se règle au back-office.
- **Tranché par** : humain
