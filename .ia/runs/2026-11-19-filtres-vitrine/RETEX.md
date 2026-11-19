# RETEX — filtres de la vitrine (2026-11-19)

## Ce qui s'est produit deux fois

- **Les tests qui figent la réponse exacte d'une liste ont encore cassé**, cette fois `UserAddressApiTest` (Modules/Auth), lancé hors des modules ciblés. C'est la quatrième fois.
  - Règle candidate (à poser au projet) : n'affirmer la forme exacte d'une liste qu'à **un** endroit (`ListingApiTest`) ; ailleurs, affirmer les valeurs utiles.
  - Règle complémentaire : lancer la suite complète avant de commiter.
- **Typage des routes Expo** : une nouvelle route n'est connue de `tsc` qu'une fois les types régénérés (`.expo/types/router.d.ts`, par Metro en marche). L'attendre avant de conclure à une erreur.

## Règle ré-implémentée / domiciliée

- **Les filtres** sont domiciliés dans `Listing` (module Form), comme la recherche et les épinglées. Le domaine ne fait que les déclarer et dire comment les appliquer.
- **L'écran des filtres** réutilise le formulaire générique (`DynamicForm`) au lieu d'un nouveau rendu de champs.
