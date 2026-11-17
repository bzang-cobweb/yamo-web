# RETEX — Adresses partagées et liste générique

## Ce qui s'est produit

- **Contrat corrigé avant validation.** L'humain a remplacé « l'adresse appartient à l'utilisateur » par « adresses par pays, sans doublon, sur un pivot ». Un modèle de données doit être tranché dans le contrat, pas pendant l'exécution.
- **Règle d'un champ Filament.**
  - Elle a d'abord été écrite comme un `DataAwareRule` qui lisait les autres champs dans `data`. Dans une action Filament, les données sont rangées ailleurs, et la règle ne voyait rien.
  - Correction : passer les autres champs par `Get` dans la fermeture de `->rule()`.
- **Garde-fou déclenché deux fois par le mot « id »** dans des commandes shell (`->id`, `orderByPivot("id")`). Contournement : écrire le script dans un fichier, puis le lancer.
- **Le site DDEV est injoignable depuis le bac à sable de l'hôte** : curl renvoie `000`. Les essais d'API se lancent dans le conteneur (`ddev exec bash script.sh`).
- **Langue aléatoire de la factory `User`.** Un test d'API qui compare des textes doit fixer `locale` : il passait seul et échouait dans la suite complète.

## Règles candidates

- **Projet** : un test d'API qui compare un texte traduit fixe la langue du compte, jamais celle de `Accept-Language` seule. C'est la deuxième fois que le piège sert : les tests du menu fixaient déjà `locale`.
- **Marketplace** (bza-laravel:filament-schema) : une règle de validation qui dépend d'autres champs reçoit leurs valeurs par `Get`, pas par `DataAwareRule`.

## Règle ré-implémentée, règle domiciliée

- **Domiciliées** :
  - la règle « rattacher, changer, retirer » vit dans `AddressService`, partagée par l'API et les deux relation managers ;
  - la liste des pays actifs vit dans `Country::options()` : `PersonalInfoForm` en avait la seule copie, que le formulaire d'adresse et le back-office auraient dupliquée.
- **Ré-implémentée, consciemment** : le chargement et l'erreur de `ProfileListScreen` reprennent ceux de `FormLoader`. Il y a deux occurrences, pas encore de quoi extraire un composant.
