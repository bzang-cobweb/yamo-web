# RETEX — 2026-11-20-pays-unique-identifiant-double

## Ce qui s'est produit deux fois

- J'ai oublié d'attendre (`await`) les événements de la bibliothèque de test dans les tests de l'app : deux fois dans ce chantier (`FieldConditions`), et déjà au chantier « suppression de compte ». La version 14 de la bibliothèque rend `fireEvent` asynchrone.
  - **Règle candidate** (projet mobile) : `await fireEvent.*` partout, et `findBy*` après un événement qui change l'affichage.
- J'ai rangé un chemin d'outil dans une variable shell, et le garde-fou l'a refusé : c'est la deuxième fois dans la session pour l'outil `.env`.
  - **Règle tenue** : le skill le dit déjà. L'appeler par son chemin complet, toujours.

## Règles ré-implémentées / domiciliées

- Domiciliée : « une seule option, choisie d'office et verrouillée » vit dans le module Form (`Field::isLocked`). Tous les formulaires servis à l'app en profitent sans y penser. Le back-office a son équivalent unique, `SingleCountry`.
- Domiciliée : l'affichage conditionnel (`visible_when`) est générique, réutilisable au-delà de l'identifiant.
