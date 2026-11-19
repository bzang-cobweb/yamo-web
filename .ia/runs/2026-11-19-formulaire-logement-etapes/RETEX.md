# RETEX — formulaire de logement par étapes, symbole de devise (2026-11-19)

## Ce qui s'est produit deux fois

- **Un test qui fige un affichage a été manqué** par le lancement ciblé des tests : `tests/Feature/CountrySpaceTest.php` attendait « XAF / mois ». C'est la troisième fois en trois chantiers que ce fichier, hors des modules touchés, casse.
  - Règle candidate (projet, à poser) : lancer **toute** la suite serveur avant chaque commit d'un changement d'affichage, pas seulement les modules touchés.
- **Une lecture en base dans une déclaration** (`Domain::attributes()`) a cassé tous les tests, qui démarrent avant les migrations.
  - Règle candidate : une déclaration de domaine (attributs, étapes, catégories) ne lit jamais la base ; ce qui dépend du contexte (pays, devise) est appliqué par qui affiche.

## Règle ré-implémentée / domiciliée

- **Le symbole d'une devise** est domicilié dans `App\Support\Currency`. Le code brut concaténé a été retiré du formulaire et de la colonne prix du back-office.
- **Le déroulé à étapes** existant (inscription) est réutilisé tel quel pour les offres. Seul l'ordre des champs dans une étape a été corrigé, pour tous les formulaires.
