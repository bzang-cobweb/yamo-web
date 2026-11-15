# `2026-11-15` — « Voir » est requis pour toute page d'une ressource Filament

- **Type** : conséquence de conception
- **Découle de** : Filament autorise l'accès à une ressource par `viewAny` avant toute page (création, édition comprises).
- **Ce que ça implique concrètement** : un rôle qui a `users.create` sans `users.view` ne peut pas ouvrir la page de création ; il faut lui donner les deux.
- **Ce n'est pas un défaut** : comportement du framework ; les tests du module donnent `view` avec chaque permission d'écriture.
