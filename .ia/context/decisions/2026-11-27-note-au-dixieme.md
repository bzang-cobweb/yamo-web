# `2026-11-27` — La note d'un avis au dixième, choisie au curseur

Complète : 2026-11-19-contrat-avis-offres.md

- **Type** : décision
- **Question posée** : « proposer un moyen plus joli pour le champ qui permet de donner une note utilisé dans reviews avec la possibilité de donner une note qui évolue de 0.1 genre 3.2 » ; trois maquettes présentées, choix humain : « option b » (étoiles et curseur).
- **Ce qui est retenu** :
  - la note d'un avis va de 1,0 à 5,0 au dixième (`offer_reviews.rating` en `decimal(2,1)`, migration ; les notes entières deviennent 4,0) ; le type `rating` se valide « numérique, entre 1 et 5, multiple de 0,1 » ;
  - le champ de l'app montre la note en grand (« 3,2 / 5 », un tiret sans note), les étoiles remplies au dixième, et un curseur dessous (glisser le pouce ou toucher la piste ; l'accessibilité règle de 0,1 en 0,1) ; fait maison avec `PanResponder`, sans nouvelle dépendance ;
  - partout, les étoiles d'affichage se remplissent au dixième (`Stars`) ;
  - back-office : la note « 3,2 ★ » ; le filtre par étoile garde les notes de n,0 à n,9.
- **Tranché par** : humain
