# Filtres de la vitrine

Demande du 2026-11-19 :

> 3. ajouter un lien filtre à droite en dessous de la barre de recherche qui a un badge qui indique le nombre de filtre cocher
> 4. filtrer selon le type, la ville, ... comment déterminer les autres critères de filtres ?

## Comment choisir les critères

Un bon filtre remplit trois conditions :

1. **Il tranche une décision de la personne qui cherche**, dans l'ordre où elle la prend : d'abord où et combien, puis quel genre de logement, puis le confort.
2. **Il porte sur une donnée structurée**, c'est-à-dire un choix, un nombre, une case ou une date. Une donnée enregistrée comme du texte libre se filtre mal.
3. **Il est renseigné sur la plupart des offres.** Une offre sans la donnée disparaît dès qu'on filtre dessus, ce qui donne l'impression qu'il n'y a « rien ».

Appliqué au logement, on obtient :

| Critère | Pourquoi | Champ |
|---|---|---|
| **Ville** | La première question (« où ? »). La ville vient de l'adresse, toujours renseignée. | choix parmi les villes des offres publiées du pays |
| **Loyer min / max** | La deuxième question (« combien ? »). | deux nombres, dans la devise du pays |
| **Type** | Appartement, maison, studio, chambre : la même ville et le même budget, mais pas le même besoin. | cases à cocher (plusieurs types) |
| **Meublé** | Il change tout : un prix au jour ou au mois, un séjour court ou une location longue. | « Tous / Meublé / Non meublé » |
| **Chambres (au moins)** | Le critère familial le plus courant. | 1, 2, 3, 4 et plus |
| **Disponible dès le** | Utile pour qui déménage à une date précise. | une date |

**Écartés pour l'instant** :
- **Catégorie** : elle recoupe « Meublé » et le type, ce qui ferait un doublon pour l'utilisateur.
- **Surface et pièces** : elles sont facultatives, donc trop souvent vides.
- **Note** : peu d'avis au lancement.
- **Quartier** : ce n'est pas une donnée structurée aujourd'hui.

Plus tard, ce que les gens tapent dans la recherche dira quel critère manque. Par exemple, si « Bastos » revient souvent, il faudra ajouter le quartier.

## Ce que j'ai compris

### App

- **Lien « Filtres »** :
  - sous la barre de recherche, aligné à droite, avec une icône ;
  - un **badge** rond indique le nombre de filtres actifs (« 2 »). Il est absent quand aucun filtre n'est actif ;
  - le loyer min et max comptent pour un seul filtre.
- **Le toucher** ouvre un écran « Filtres » :
  - les champs décrits par le serveur, comme un formulaire ;
  - en bas, « Afficher les offres » applique les filtres, et « Effacer » les remet tous à zéro.
- **Retour à la vitrine** :
  - la liste se recharge avec les filtres ;
  - la recherche par texte se combine avec eux ;
  - le défilement infini et les offres en vedette (en tête) les respectent aussi.
- **Durée de vie** : les filtres restent tant que l'app est ouverte, et se perdent à la fermeture.
- **Visiteur ou membre** : même comportement.
- **Liste vide** : « Aucune offre ne correspond à ces filtres. »

### Serveur (générique, comme les listes et les formulaires)

- **Déclaration** : une liste peut déclarer ses filtres (`Listing::filters()`), sous forme de champs (`Field`) servis à l'app avec la liste. Une liste sans filtres ne montre pas le lien.
- **Paramètres** : ils sont passés dans l'URL (`?filter[city]=Douala&filter[type][]=studio`). Chaque filtre est validé par son champ. Un filtre invalide ou inconnu est ignoré, sans erreur.
- **Pour les offres** :
  - **ville**, **loyer** min et max : champs communs, déclarés par la vitrine ;
  - **type**, **meublé**, **chambres**, **disponible dès** : déclarés par le **domaine** (`Domain::filters()`), avec la façon de les appliquer (lecture dans `details`) ;
  - un autre domaine (une boutique) déclarera les siens.
- **Textes** en français et en anglais.

### Hors périmètre

- Garder les filtres d'une session à l'autre, ou les enregistrer comme alerte.
- Un tri au choix (prix croissant, plus récentes, etc.).
- Une carte.
- Des filtres sur « Mes logements » ou « Mes favoris ».

## Découpage

1. **Serveur** :
   - `Listing::filters()` et l'application des filtres ;
   - les filtres de la vitrine et de `RentalDomain` ;
   - les villes proposées ;
   - les textes, l'OpenAPI et les tests.
2. **App** :
   - le lien « Filtres » et son badge ;
   - l'écran des filtres, à partir des champs servis ;
   - la vitrine qui transmet les filtres ;
   - les tests.
3. **Vérification** au simulateur, puis QA indépendante.

## Critères de succès

- **Lien et badge** : sous la recherche, à droite, un lien « Filtres ». Son badge compte les filtres actifs et disparaît à zéro.
- **Filtrer** :
  - choisir Douala, « Studio » et un loyer maximum ne laisse que les studios de Douala sous ce loyer ;
  - la recherche, le défilement infini et les vedettes respectent les filtres.
- **Effacer** : toutes les offres reviennent, et le badge disparaît.
- **Robustesse** : un filtre invalide ou inconnu dans l'URL ne casse rien.
- **Tests** : les suites serveur et app passent, ainsi que tsc et le lint.

## Ce que j'ai décidé seul

- **Champs des filtres** : ce sont des `Field` ordinaires, réutilisés par l'écran des filtres de l'app (`DynamicForm`). Ce que la recherche fait déjà, les filtres le font aussi : les épinglées et les pages passent par `applyFilters()`.
- **Validation** : un filtre est validé seul par son champ (`validFilters()`). Un filtre invalide, inconnu ou vide est ignoré ; « Tous » (`any`) ne filtre rien.
- **« Libre le »** : une offre sans date est libre tout de suite, donc gardée. Laravel traite aussi une date JSON `null` sur MariaDB (`json_type(...) = 'NULL'`, vérifié).
- **Villes proposées** : celles des offres publiées du pays, par ordre alphabétique.
- **Badge** : `price_min` et `price_max` comptent pour un (règle de l'app : un nom en `_min`/`_max` forme un groupe). Une valeur égale à la valeur par défaut (« Tous ») ne compte pas.
- **Mémoire de l'app** : les filtres sont gardés dans un petit magasin partagé entre la vitrine et l'écran des filtres, en mémoire seulement. Ils se perdent à la fermeture, comme prévu.
- **Écran des filtres** : il est ouvert à tous (route `/filters/[list]`), sans connexion.

## Ce qui a été fait

- **Serveur** :
  - `Listing::filters()`, `validFilters()`, `applyFilters()` ; filtres appliqués aux pages et aux épinglées ;
  - `filters` servi avec chaque liste ;
  - `Domain::filters()` et `applyFilters()`, avec leur version pour `RentalDomain` (type, meublé, chambres, libre le) ;
  - ville, loyer min et max dans la vitrine ;
  - textes fr/en et `openapi.json` régénéré.
- **Tests serveur** : `OfferFiltersApiTest` (4 tests : description des filtres, combinaisons et recherche, filtres invalides ignorés, vedettes et pages filtrées) ; 2 tests mis à jour. La suite complète passe.
- **App** :
  - le lien « Filtres » et son badge dans `List` ;
  - le magasin des filtres et le compte du badge ;
  - `FilterScreen` et sa route ;
  - les filtres dans l'URL de la liste ;
  - le texte « Aucune offre ne correspond à ces filtres ».

  214 tests passent, ainsi que tsc et le lint.
- **Simulateur** : l'écran des filtres affiche les 7 champs servis. « Meublé » appliqué donne le badge 1 et uniquement des meublés.
- **API locale (MariaDB)** : type, chambres, ville, loyer et date vérifiés.
- **QA indépendante** : GO, 0 bloquant ; ses 2 mineurs sont traités (test d'adresse complété, date JSON `null` vérifiée).

## Ce que je n'ai pas vérifié

- **Simulateur** : cocher plusieurs types (le toucher sur « Studio » n'a pas pris), « Effacer les filtres », la combinaison avec la recherche. Ces cas sont couverts par les tests seulement.
- **Performance** : celle de la requête des villes avec beaucoup d'offres.
- **Anglais** : l'écran des filtres n'a pas été vu en anglais.

## Ce qui reste en suspens

- Hors périmètre, prévus plus tard :
  - le tri ;
  - les filtres gardés d'une session à l'autre, et les alertes ;
  - la carte ;
  - le quartier comme critère (quand la recherche le montrera).
