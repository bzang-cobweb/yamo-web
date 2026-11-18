# `2026-11-18` — Bouton d'ajout fixe « + », hors du défilement de la liste

- **Type** : décision
- **Remplace en partie** : `2026-11-18-adresse-app-simplifiee.md`, point 3 (bouton d'ajout au-dessus des items, dans la liste qui défile).
- **Question posée** : le bouton d'ajout doit-il rester dans la partie qui défile ? Recommandation : un « + » fixe à droite du champ de recherche.
- **Réponse** : « Yes, fixed "+" ».
- **Ce qui est retenu** :
  - le composant générique `List` porte une barre fixe au-dessus de la partie qui défile. Elle contient le champ de recherche, s'il y en a un, et un bouton « + » à droite, s'il y a une création ;
  - sans recherche, le « + » est seul, aligné à droite ;
  - le libellé de création servi par l'API devient le nom du bouton lu par l'accessibilité ;
  - le message de retour et l'erreur restent en tête de la liste.
- **Motif** : avec le défilement infini, un bouton placé en tête de la liste disparaît dès qu'on descend.
- **Portée** : app mobile — `List` et `ProfileListScreen`.
- **Corollaires** :
  - le libellé « Ajouter une adresse » n'est plus affiché en texte : il sert de nom accessible au « + » ;
  - ni la barre du haut ni un bouton flottant ne sont touchés.
- **Tranché par** : humain
