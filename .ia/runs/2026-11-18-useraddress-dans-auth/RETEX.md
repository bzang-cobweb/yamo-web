# RETEX — UserAddress déplacé dans Auth

## Ce qui s'est produit

- **Un test déplacé n'était plus exécuté, sans aucun message.** Le fichier avait été renommé (`UserAddressApiTest.php`) mais pas sa classe (`AddressApiTest`). La suite complète a ignoré le fichier : 305 tests au lieu de 319. C'est la baisse du total qui l'a révélé.
- **`git add -A` a été refusé** par le garde-fou. Les fichiers renommés avec `git mv` sont déjà indexés, et `git add` sur leur ancien chemin échoue.
- **`pint --dirty` n'a pas vu les fichiers déplacés**, déjà indexés : il a fallu lancer Pint sur les modules entiers.
- **Un fichier touché par le chantier portait aussi une modification locale de l'humain** (`Listing.php`, `perPage()`). Au chantier précédent, j'ai commité mes lignes seules avec `git update-index --cacheinfo`. Ce chantier n'y a pas touché.

## Règles candidates

- **Projet** : après tout déplacement ou renommage de test, comparer le nombre de tests avant et après. PHPUnit ignore sans rien dire une classe dont le nom ne correspond pas au fichier.
- **Marketplace (bza-laravel)** : vérifier qu'un fichier PHP déplacé déclare bien la classe de son nom, avec un petit script (nom du fichier comparé à la déclaration `class`).

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : la règle des adresses d'un compte vit dans `UserAddressService` (Auth). Celle de l'adresse partagée vit dans `AddressService` (Location). Les dépendances ne vont plus que d'Auth vers Location.
