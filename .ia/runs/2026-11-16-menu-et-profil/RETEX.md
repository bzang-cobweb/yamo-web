# RETEX — chantier 2026-11-16-menu-et-profil

## Ce qui s'est produit deux fois → règles candidates

1. **Le simulateur révèle ce que les tests ne voient pas**, pour le troisième chantier
   de suite. Ici, `Link asChild` perdait le style calculé de `Pressable`, et le titre
   de l'accueil n'était pas centré. Règle candidate confirmée : pas de livraison
   d'écran sans capture dans le simulateur.
2. **Un compte réel est déjà connecté dans l'app** : l'humain s'était reconnecté
   entre-temps. On ne peut pas tester un enregistrement sans modifier ses données, ni
   se déconnecter sans casser sa session. Règle candidate : pour vérifier une
   écriture dans l'app, demander à l'humain un créneau avec un compte de test, ou
   l'annoncer et différer. Ne jamais écrire sur le compte réel.
3. **Les garde-fous du shell ont coûté deux relances** :
   - en zsh, la variable `path` écrase `PATH` ;
   - `git add -A` est refusé même limité à des chemins.

   Règles candidates : ne jamais nommer une variable `path` dans un script zsh ;
   `git add -- <dossier>` suffit pour les suppressions.

## Autres leçons

- **Fabriques `jest.mock`** : elles ne peuvent utiliser que des variables préfixées
  `mock` ou `jest.requireActual`.
- **Un `setState` dans un effet** est refusé par le lint React. Dériver l'affichage
  du paramètre est plus simple et plus sûr.
- **Les routes typées d'Expo** se régénèrent au démarrage d'`expo start`.

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliées** :
  - les FormRequest des formulaires dans `FormBackedRequest` ;
  - l'écran d'un formulaire du profil dans `ProfileFormScreen` ;
  - les liens du profil dans `ProfileMenuLink`.
- **À surveiller** :
  - `LoginRequest` pourrait étendre `FormBackedRequest`, en ajoutant `device_name` ;
  - la gestion des erreurs d'API reste dupliquée (3 copies).
