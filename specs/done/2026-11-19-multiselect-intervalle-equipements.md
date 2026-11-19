# Liste à choix multiples, champ intervalle, équipements

Demande du 2026-11-19 :

> 1. ajouter un multiselect pour le type dans les filtres
> 2. ajouter un champ d'un nouveau type pour déterminer une borne inf et borne sup (utiliser pour loyer min et max dans les filtres)
> 3. ajouter des attributs comme wifi, parking, ... et les mettre dans les filtres

## Ce que j'ai compris

Deux nouveaux **types de champ génériques**, utilisables par tout formulaire et toute liste (app, API, back-office), puis leur usage dans les offres.

### 1. Type `multiselect` : une liste à choix multiples

- **Dans l'app** :
  - un champ comme une liste déroulante, qui affiche les choix faits : « Studio, Maison », ou « Choisir… » quand rien n'est choisi ;
  - le toucher ouvre la liste des options avec une case par option ; on en coche plusieurs, puis « Valider ».
- **Valeur** : la liste des options choisies, comme les cases à cocher actuelles. La validation et l'affichage d'une valeur se font de la même façon (« Studio, Maison »).
- **Back-office** : une liste déroulante à choix multiples.
- **Filtre « Type »** de la vitrine : il passe de cases à cocher à cette liste.

### 2. Type `range` : un intervalle, une borne inférieure et une borne supérieure

- **Dans l'app** :
  - deux saisies côte à côte, « Min » et « Max », sous un seul libellé ;
  - l'unité s'affiche dans la ligne d'aide (« En FCFA »), comme pour les autres champs.
- **Valeur** :
  - les deux bornes `[min, max]` ; chacune peut rester vide (« jusqu'à 100 000 », « à partir de 50 000 ») ;
  - un intervalle vide ne filtre rien ;
  - la borne max doit être supérieure ou égale à la borne min, sinon une erreur s'affiche sous le champ.
- **Back-office** : deux nombres côte à côte.
- **Filtre « Loyer »** de la vitrine : un seul champ `range` (`price`) remplace « Loyer minimum » et « Loyer maximum ». Le badge le compte naturellement pour un filtre, et la règle spéciale `_min`/`_max` de l'app est retirée.

### 3. Équipements du logement

- **Un nouvel attribut du domaine location**, « Équipements » (`amenities`) :
  - c'est une liste à choix multiples (`multiselect`), facultative ;
  - il est saisi dans le formulaire de l'app (à l'étape « Le logement ») et dans le back-office (onglet « Caractéristiques ») ;
  - la fiche l'affiche dans ses caractéristiques (« Wifi, Parking, Climatisation »).
- **Liste proposée**, pensée pour le Cameroun d'abord :

  | Clé | Libellé |
  |---|---|
  | `wifi` | Wifi |
  | `parking` | Parking |
  | `air_conditioning` | Climatisation |
  | `hot_water` | Eau chaude |
  | `generator` | Groupe électrogène |
  | `water_tank` | Réserve d'eau / forage |
  | `security` | Gardien / sécurité |
  | `equipped_kitchen` | Cuisine équipée |
  | `washing_machine` | Lave-linge |
  | `tv` | Télévision |
  | `balcony` | Balcon / terrasse |
  | `pool` | Piscine |

  La liste est déclarée dans `RentalDomain`, avec ses textes en français et en anglais. On la complète sans toucher au reste.
- **Filtre « Équipements »** dans la vitrine (`multiselect`) : une offre doit avoir **tous** les équipements choisis. Par exemple, « Wifi + Parking » ne garde que les offres qui ont les deux.
- **Démo** : les logements de démonstration reçoivent quelques équipements, pour que le filtre donne des résultats.

### Hors périmètre

- Des icônes par équipement.
- L'affichage des équipements dans l'item de liste.
- Un intervalle de dates.

## Découpage

1. **Serveur** :
   - `FieldType::MULTISELECT` et `RANGE` (règles, affichage, back-office) ;
   - l'attribut `amenities` et les filtres de la vitrine (type en `multiselect`, loyer en `range`, équipements) ;
   - la démo, les textes, l'OpenAPI et les tests.
2. **App** :
   - `MultiSelectField` et `RangeField` ;
   - le compte du badge ;
   - les filtres dans l'URL (`filter[price][]`) ;
   - les tests.
3. **Vérification** au simulateur, puis QA indépendante.

## Critères de succès

- **Filtre « Type »** : on choisit plusieurs types dans une liste déroulante. Le champ affiche les choix (« Studio, Maison ») et la vitrine ne garde que ces types.
- **Filtre « Loyer »** : un seul champ avec deux bornes.
  - « max 100 000 » seul, « min 50 000 » seul, ou les deux filtrent correctement ;
  - un max inférieur au min affiche une erreur sous le champ.
  - Le badge compte le loyer pour un.
- **Équipements** : on les saisit dans le formulaire de l'app et dans le back-office, et ils s'affichent sur la fiche. Le filtre ne garde que les offres qui ont tous les équipements choisis.
- **Champs génériques** : `multiselect` et `range` fonctionnent dans tout formulaire (API, app, back-office). Une valeur invalide est refusée par l'API.
- **Tests** : les suites serveur et app passent, ainsi que tsc et le lint.

## Ce que j'ai décidé seul

- **Valeur d'un intervalle** : `[min, max]`, une liste de deux bornes, dans l'API, dans l'app et dans l'URL des filtres (`filter[price][]=&filter[price][]=100000`). Elle passe par le modèle de valeurs existant (texte, case ou liste), sans nouveau format.
- **Règles d'un intervalle** : les règles d'un champ `range` (`min:0`) valent pour chaque borne. Max < min est refusé par une règle générique (`form::validation.range_order`).
- **Intervalle à l'envers dans les filtres** : l'app l'arrête avant d'appliquer, avec l'erreur sous le champ. Côté API, un filtre invalide reste ignoré sans message, comme le prévoit le contrat des filtres.
- **Back-office** : `FieldComponent::component()` (nouveau) rend un intervalle en deux nombres côte à côte ; `make()` reste le composant d'un champ simple.
- **Barre fixe en bas de l'écran des filtres** (demandée en cours de route) : l'écran affiche ses champs lui-même, au lieu du formulaire générique, pour poser « Effacer les filtres » et « Afficher les offres » hors de la zone qui défile.
- **Badge** : la règle `_min`/`_max` de l'app est retirée ; un intervalle aux deux bornes vides ne compte pas.
- **Équipements** :
  - 12 équipements, déclarés dans `RentalDomain::AMENITIES` ;
  - le filtre utilise `whereJsonContains`, un équipement après l'autre (tous doivent être présents) ;
  - dans la démo, 6 jeux d'équipements sont distribués tour à tour, et les charges des meublés sont retirées.

## Ce qui a été fait

- **Serveur** :
  - `FieldType::MULTISELECT` et `RANGE`, `Field::multiselect()` et `range()` ;
  - règles, affichage (« A, B », « 50 – 100 », « ≥ 50 », « ≤ 100 ») et back-office (`Select` multiple, `Fieldset` Min/Max) ;
  - attribut `amenities` (étape « Le logement », onglet « Caractéristiques », fiche) ;
  - filtres de la vitrine : type et équipements en `multiselect`, loyer en `range` ;
  - démo, textes fr/en, `openapi.json`.
- **Tests serveur** : 486 tests passent dans la suite complète. Sont nouveaux :
  - `FieldTest` pour le multiselect et l'intervalle ;
  - `FieldComponentRangeTest` ;
  - les équipements dans `OfferApiTest` ;
  - l'intervalle à l'envers et les équipements dans `OfferFiltersApiTest`.
- **App** :
  - `MultiSelectField` (liste, cases, « Valider ») et `RangeField` (Min/Max côte à côte) ;
  - les deux nouveaux types dans `FormField` et les valeurs de départ ;
  - le compte du badge ;
  - `FilterScreen` avec sa barre fixe en bas et l'erreur d'intervalle.

  216 tests passent, ainsi que tsc et le lint.
- **Simulateur** :
  - l'écran des filtres montre « Loyer » Min/Max (« En FCFA ») et les listes à choix multiples ;
  - « Wifi + Parking » donne le badge 1 et seulement les logements qui ont les deux ;
  - la fiche affiche « Équipements : Wifi, Cuisine équipée, Lave-linge ».
- **QA indépendante** : NO-GO (pas d'erreur pour un intervalle à l'envers dans les filtres ; intervalle absent du back-office). Corrigé, puis GO.

## Ce que je n'ai pas vérifié

- **Back-office** : non revu dans un navigateur. La règle max ≥ min y est couverte sans test de soumission.
- **Simulateur** : l'erreur d'intervalle et le clavier sur la barre fixe. Ils sont couverts par jest seulement.
- **Anglais** : l'app n'a pas été vue en anglais.
- **Formulaire de l'app** : la saisie des équipements n'a pas été faite dans le formulaire d'un logement, seulement couverte par les tests.

## Ce qui reste en suspens

- Hors périmètre : des icônes par équipement, et les équipements dans l'item de liste.
- **Textes de l'app** : ils sont chargés au démarrage, donc un nouveau texte n'apparaît qu'après un redémarrage de l'app. Cela n'a rien d'un défaut, mais c'est à savoir pendant le développement.
