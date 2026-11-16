# RETEX — chantier 2026-11-16-photo-de-profil

## Ce qui s'est produit deux fois → règles candidates

1. **Une API de la plateforme qui a changé sous nos pieds** :
   - côté web, `upload_dirs` de DDEV est relatif au docroot ;
   - côté mobile, le `fetch` global est désormais `expo/fetch`, qui ne lit plus la forme
     `{ uri }` de React Native.

   Les deux échouaient en silence, et seul un essai réel les a révélés : les tests
   automatisés (faux disque, `fetch` simulé) ne pouvaient pas les voir. Règle candidate,
   pour tous les projets : **une fonctionnalité qui touche des fichiers ou le réseau
   n'est pas livrée sans un essai réel** (`curl` contre DDEV, simulateur), en plus des
   tests.
2. **Les faux de test diffèrent du vrai** :
   - `Storage::fake` sert des adresses signées sans `/storage` ;
   - le `FormData` de jest est celui de Node ;
   - l'adresse de base des tests est `APP_URL`, pas `localhost`.

   Trois assertions ont été réécrites. Règle candidate : dans un test qui passe par un
   faux, vérifier le **chemin sur le disque** ou l'objet produit, pas l'adresse rendue.
3. **La fabrique tire la langue du compte au hasard** : un test sur un message traduit a
   échoué selon le tirage (déjà vu au chantier précédent avec Faker). Règle candidate,
   pour le projet : un test qui lit un texte traduit fixe la langue du compte.

## Autres leçons

- **Expo Go garde l'ancien code** tant qu'on ne l'a pas fermé
  (`xcrun simctl terminate booted host.exp.Exponent`) : même famille que « Metro sert un
  code périmé » au chantier précédent.
- **Le sélecteur d'iOS met quelques secondes à s'ouvrir** après la fermeture d'une
  alerte : attendre avant de conclure qu'il n'a pas réagi.
- **L'audit avant le code a payé** : conversions mises en file sans worker, adresses
  devinables, lien `public/storage` cassé, test de connexion à réponse exacte. Les
  quatre ont été traités avant d'écrire.

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliées** :
  - les règles d'envoi et l'optimisation de la photo, dans `AvatarService` (API et
    Filament) ;
  - l'envoi de fichier côté mobile, dans `shared/api/form-file.ts` et
    `ApiClient.postForm`.
- **À surveiller** : la gestion d'erreur d'API reste dupliquée entre `DynamicForm` et
  `AvatarPicker` (ApiError, NetworkError, inconnue). C'est candidat à une fonction
  partagée `messageOf(error, t)`.
