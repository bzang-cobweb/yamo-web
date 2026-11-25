# `2026-11-25` — Fiche d'une offre : les avis en carrousel

- **Type** : décision
- **Question posée** : « page de détail offre faire un carousel de 10 (valeur config application) reviews ».
- **Ce qui est retenu** :
  - **Combien** : la fiche sert les 10 derniers avis. Le nombre se règle dans `config('offer.reviews_shown')`, qui valait 1.
  - **Comment** : dans la section « Avis », sous la note moyenne, un carrousel horizontal.
    - Une carte par avis (prénom, date, étoiles, commentaire sur 4 lignes au plus), sur un fond léger et sans bordure (décision 2026-11-25-fiche-bordures-sections).
    - Chaque carte fait 85 % de la largeur, pour laisser voir la suivante. Le défilement s'arrête sur une carte.
    - Un seul avis prend toute la largeur.
  - **Le reste** : « Voir tous les avis » ouvre toujours la liste complète.
- **Tranché par** : humain (10, dans la configuration) ; session (la taille des cartes, 4 lignes de commentaire)
