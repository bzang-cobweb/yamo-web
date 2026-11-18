# RETEX — Recherche dans les listes

## Ce qui s'est produit

- **Un test de recherche a échoué une fois sur six.** Le destinataire tiré au hasard par la factory (« Lacroix ») correspondait à la recherche « lac ». Fixer les valeurs qui comptent pour l'assertion a réglé le problème.
- **L'échappement d'un `LIKE` diffère entre MariaDB et SQLite.** La fonction `suggest()` du back-office échappe avec `\` sans clause `ESCAPE`. Cela marche sur MariaDB, mais sous SQLite `%` et `_` y restent des jokers. Ce n'est pas corrigé ici, car c'est hors périmètre.
- **Une modification locale de l'humain (`perPage()` à 3)** se trouvait dans l'arbre de travail pendant le chantier précédent. Le commit par chemin l'a laissée de côté, comme il se doit.

## Règles candidates

- **Projet** : un test qui cherche du texte fixe toutes les valeurs textuelles des données créées, et ne laisse aucune donnée de factory au hasard.
- **Projet** : une recherche `LIKE` passe par un seul helper, avec `ESCAPE '!'`. Aujourd'hui, `Listing::orWhereContains()` le fait, mais `AddressService::suggest()` ne s'en sert pas encore.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : la recherche dans les listes de l'app vit dans `Listing::orWhereContains()`.
- **Ré-implémentée** : `AddressService::suggest()` fait son propre `LIKE`, avec une autre façon d'échapper.
