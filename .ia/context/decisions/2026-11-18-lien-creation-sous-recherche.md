# `2026-11-18` — Recherche sur toute la ligne, lien de création dessous, hors du défilement

- **Type** : décision
- **Remplace** : `2026-11-18-bouton-ajout-fixe.md`.
- **Question posée** : aucune ; consigne de l'humain après la mise en place du « + » fixe.
- **Réponse** : « garder la recherche sur toute la ligne et ajouter le lien de création en dessous aligné à droite dans la zone hors du contenu ».
- **Ce qui est retenu** :
  - dans le composant `List`, la zone fixe au-dessus de la partie qui défile contient d'abord le champ de recherche, s'il y en a un, sur toute la largeur ;
  - en dessous vient le lien de création, aligné à droite, avec son libellé servi par l'API (« Ajouter une adresse ») ;
  - le « + » seul disparaît.
- **Motif** : une recherche pleine largeur, et une action de création lisible en toutes lettres, toujours à portée.
- **Portée** : app mobile — `List` et `ProfileListScreen`.
- **Corollaires** : sans recherche, la zone fixe ne contient que le lien.
- **Tranché par** : humain
