# Formulaire de logement par étapes, unités dans l'aide

Demande du 2026-11-19 :

> 1. formulaire d'un logement mobile par étape
> 2. utiliser le helper des champs pour mettre les unités (prix, surface,...)

Précisions de l'humain :
- **unités** : « Unit in the help text » ;
- **étapes** : « 4 steps » :
  1. le logement ;
  2. le prix et la disponibilité ;
  3. l'adresse et la catégorie ;
  4. l'annonce.

## Ce que j'ai compris

### 1. Le formulaire en 4 étapes

Ajouter ou modifier un logement dans l'app se fait en quatre étapes, avec le déroulé des formulaires à étapes déjà en place (inscription) :
- « Étape 2 sur 4 » et le titre de l'étape s'affichent en haut ;
- « Suivant » valide l'étape auprès du serveur avant de passer à la suivante ;
- « Précédent » revient en arrière sans rien perdre ;
- le dernier bouton enregistre ;
- les erreurs du serveur s'affichent sous les champs de l'étape courante.

Les quatre étapes :

| Étape | Titre | Champs |
|---|---|---|
| 1 | Le logement | type, meublé, surface, pièces, chambres |
| 2 | Prix et disponibilité | loyer, charges, disponible dès le |
| 3 | Adresse et catégorie | adresse, catégorie |
| 4 | L'annonce | titre, description, statut |

- **Les étapes restent génériques** : le formulaire `offer.offer` ne connaît pas le logement. C'est le **domaine** qui déclare ses étapes (`Domain::formSteps()`), en nommant ses attributs et les champs communs (prix, adresse, catégorie, titre, description, statut).
- **Domaine sans étapes** : son formulaire reste d'un seul tenant, comme aujourd'hui.
- **Champ non placé** : un champ qu'aucune étape ne cite rejoint la dernière étape, pour qu'aucun champ ne soit perdu.
- **Modifier une offre** suit les mêmes étapes, préremplies.
- **Back-office** : il n'est pas concerné ; le formulaire Filament reste d'un seul tenant.

### 2. Les unités dans l'aide

Dans l'app, l'unité d'un champ ne s'affiche plus à droite de la saisie. Elle passe dans la ligne d'aide grise, sous le champ :
- loyer : « En XAF / mois », et « En XAF / jour » si meublé (l'unité suit toujours la case « meublé ») ;
- surface : « En m² » ;
- charges : « En XAF / mois ». Elles n'avaient pas d'unité ; elles en reçoivent une, la devise du pays.

Une aide existante est gardée, et l'unité vient après elle (« En plus du loyer. En XAF / mois »).

- **Côté serveur** : les unités restent déclarées sur le champ (`suffix`, `suffixWhen`). Seul l'endroit d'affichage change dans l'app. La fiche de l'offre continue d'afficher la valeur avec son unité (« 85 m² », « 5 000 XAF / mois »).
- **Back-office** : l'unité reste à droite de la saisie (convention Filament).

### Hors périmètre

- Un brouillon enregistré à chaque étape (l'enregistrement se fait à la fin, comme aujourd'hui).
- Les photos dans le formulaire de l'app.

## Découpage

1. **Serveur** :
   - `Domain::formSteps()`, les étapes de `RentalDomain` et leurs textes fr/en ;
   - `OfferForm::steps()` ;
   - l'unité des charges ;
   - les tests (étapes, validation d'une étape, champ non placé).
2. **App** : l'unité dans la ligne d'aide (`NumberField`, unité suivant un autre champ), et les tests.
3. **Vérification** au simulateur (ajouter un logement, puis le modifier) et QA indépendante.

## Critères de succès

- Dans l'app, ajouter un logement se fait en 4 étapes :
  - chaque étape montre les champs prévus ;
  - « Suivant » refuse une étape invalide, avec l'erreur sous le champ ;
  - l'offre est enregistrée à la fin ;
  - modifier une offre passe par les mêmes étapes, préremplies.
- Un domaine sans étapes garde un formulaire d'un seul tenant ; aucun champ n'est perdu.
- Sous le loyer, la surface et les charges, l'aide montre l'unité. Celle du loyer change avec « meublé ». Aucune unité ne reste à droite de la saisie.
- Les suites de tests serveur et app passent, ainsi que `tsc` et le lint.

## Ce que j'ai décidé seul

- **Le symbole de devise**, ajouté à la validation (« transformer XAF en FCFA… ») :
  - il vient d'ICU (`App\Support\Currency::symbol()`) : XAF → « FCFA », EUR → « € », USD → « $US » en français ;
  - le code ISO reste en base et dans l'API ;
  - la liste des pays du back-office affiche « XAF (FCFA) » : le code saisi, puis le symbole.
- **Charges** : un attribut ne connaît pas la devise sans lire la base, et la lire au démarrage cassait tous les tests. Le champ se déclare donc **montant** (`Field::money('mois')`), et c'est qui l'affiche qui ajoute la devise :
  - le formulaire, celle du pays ;
  - la fiche, celle de l'offre ou de son pays.

  Le back-office n'affiche pas l'unité des charges.
- **Ordre des champs** : l'app affiche les champs d'une étape dans l'ordre où l'étape les cite, et non plus dans l'ordre du formulaire. Cela vaut pour tout formulaire à étapes.
- **L'unité dans l'aide** : le libellé est « En m² » (« In m² » en anglais), servi par l'API (`form.unit`) ; il vaut pour tout champ qui a une unité.
- **Étapes absentes** : une étape dont aucun champ n'est présent dans le domaine disparaît.

## Ce qui a été fait

- **Serveur** :
  - `Currency::symbol()` ;
  - `Domain::formSteps()` et les 4 étapes de `RentalDomain` (textes fr/en) ;
  - `OfferForm::steps()` (champs absents filtrés, champs non cités ajoutés à la dernière étape) ;
  - `Field::money()` et `inCurrency()` ;
  - `Domain::inputAttributes($currency)` ;
  - la fiche affiche les charges avec leur unité ;
  - la colonne prix des offres utilise `Number::currency` ;
  - `openapi.json` régénéré.
- **Tests serveur** :
  - `CurrencyTest` ;
  - les 4 étapes et la validation d'une étape (dont le refus des coordonnées à l'étape 4) ;
  - un domaine sans étapes ;
  - un champ non cité, une étape vide ;
  - les charges sur le formulaire et sur la fiche.

  Les suites Form, Offer, Location et tests/Feature passent.
- **App** :
  - `FieldShell` écrit l'unité après l'aide ;
  - `NumberField` n'affiche plus l'unité à droite ;
  - les champs d'une étape suivent l'ordre de l'étape.

  203 tests jest passent, ainsi que tsc et le lint.
- **Simulateur** :
  - étape 1 « Le logement » avec « En m² » ;
  - « Suivant » mène à l'étape 2 « Prix et disponibilité », avec « En FCFA / mois » sous le loyer et « En plus du loyer. En FCFA / mois » sous les charges.
- **QA indépendante** : GO, 0 bloquant. Ses 3 mineurs sont corrigés (commentaires périmés, test des étapes, symbole dans la liste des pays).

## Ce que je n'ai pas vérifié

- **Simulateur** : les étapes 3 et 4, l'enregistrement final et la modification d'une offre (`?record=`). Le compte de démonstration utilisé n'a pas d'adresse. Ces parcours sont couverts par les tests seulement.
- **Ordre corrigé à l'écran** : l'ordre des champs dans l'étape 2 (« Charges » avant « Disponible dès le ») n'a été vérifié que par jest, après la correction.
- **Anglais** : le rendu de l'app en anglais (« In FCFA / month »).
- **Back-office** : non revu dans un navigateur.

## Ce qui reste en suspens

- **Charges dans le back-office** : le champ n'a pas d'unité, car la devise dépend du pays choisi dans le même formulaire. À ajouter si besoin.
- **Brouillon par étape** : pas d'enregistrement à chaque étape (hors périmètre).
