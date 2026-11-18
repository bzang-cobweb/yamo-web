# RETEX — Rôle member, module Offer et profils de domaine

## Ce qui s'est produit

- **`assignRole` dans une requête d'API a levé `GuardDoesNotMatch`.** Un rôle créé à la volée y prenait la guard `sanctum`. Fixer `'web'` a réglé le problème.
- **Le `.env` local (`OFFER_DOMAIN=rental`) a fait échouer des tests** qui ne parlent pas d'offres : le menu du profil y gagnait un lien. Les tests ne doivent pas dépendre du `.env`. `phpunit.xml` fixe désormais la valeur.
- **Un nom de colonne (`attributes`) entrait en collision avec le framework.** Je l'ai repéré avant d'écrire la migration.
- **Scramble avertit quand une table n'existe pas encore.** Il faut migrer la base locale avant de régénérer `openapi.json`.

## Règles candidates

- **Projet** : toute variable d'environnement qui change le comportement d'un module a sa valeur fixée dans `phpunit.xml`.
- **Marketplace (bza-laravel:create-migration)** : ne jamais nommer une colonne comme une propriété d'Eloquent (`attributes`, `relations`, `original`, `casts`…).

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : un même schéma (`Field`) sert au formulaire de l'app, à la validation et au back-office (`FieldComponent`).
- **Domiciliée** : l'extension du menu du profil par les autres modules (`ProfileMenuLinks`).
- **Domiciliée** : la disponibilité d'un formulaire ou d'une liste (`isAvailable()`).
