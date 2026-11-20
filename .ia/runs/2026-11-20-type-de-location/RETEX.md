# RETEX — type de location

## Ce qui s'est produit deux fois (règles candidates)

- **Le contrat a changé après validation**, deux fois : les catégories gardées, puis la date de visite. Les deux fois, le travail s'est arrêté avant le code.
  - Règle candidate (projet) : après validation, relire le dernier message de l'humain avant le premier fichier touché.
- **Un champ rendu obligatoire** a cassé une vingtaine de tests de saisie.
  - Règle candidate (projet) : en rendant un attribut obligatoire, lui donner une valeur par défaut dans la fabrique d'abord.

## Règle domiciliée

- **« Meublé ou non »** n'est plus lu à cinq endroits : il vit dans `RentalTypeEnum::of($offer)->isFurnished()`.
