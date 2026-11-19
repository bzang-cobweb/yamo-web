# RETEX — adresse d'une offre : recherche par la rue (2026-11-19)

## Ce qui s'est produit deux fois

- **Le registre n'a pas été relu avant le contrat.** La demande rétablissait une capacité que l'humain avait fait retirer la veille : les suggestions de rue, retirées de l'app puis du serveur. Je ne l'ai vu qu'au moment où des tests ont échoué. Le contrat a été validé sans que l'humain sache qu'il revenait sur ses propres décisions.
  - Règle candidate (méthode) : avant d'écrire un contrat, chercher dans `.ia/context/decisions/` les mots clés de la demande (« suggestion », « adresse »…) et citer toute décision contraire dans le contrat.
- **Du code déjà présent a failli être recopié** : `AddressService::suggest()` et `findOrCreate()` existaient (back-office). La première version les dupliquait ; c'est corrigé.
  - Règle candidate : avant d'écrire une requête métier, chercher un service du module qui la fait déjà.
- **La documentation de l'API casse avec une réponse construite à la main** (`response()->json(map(...))`). Comme pour `ListItem` avant, une ressource typée règle le problème.

## Règle ré-implémentée / domiciliée

- **Retrouver ou créer une adresse** reste domicilié dans `AddressService` (module Location), désormais utilisé aussi par les offres.
- **Le champ à suggestions** est de nouveau générique (`Field::suggestions()`, `TextField`), comme avant son retrait.
