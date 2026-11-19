# Adresse d'une offre : recherche par la rue

Demande du 2026-11-19 :

> création d'un logement: rechercher une adresse en utilisant le champ rue, si elle existe on remplit tous les autres champs autrement on la créée à la sauvegarde (aucune relation avec les adresses de mon compte)

Précisions de l'humain :
- **portée de la recherche** : « Every address » — toutes les adresses du pays, y compris celles des carnets d'adresses des membres ;
- **champs** : « Street, postal code, city ».

## Ce que j'ai compris

Dans le formulaire d'un logement de l'app, l'adresse n'est plus choisie parmi les adresses du compte. Le propriétaire la saisit :

- **Rue** : une saisie avec des **suggestions**.
  - Dès 3 caractères, l'app propose les adresses existantes du pays courant dont la rue contient le texte tapé.
  - Une suggestion se lit « Rue Njo-Njo, Bonapriso — 00237 Douala ».
  - En choisir une remplit le code postal et la ville.
- **Code postal** : facultatif, parce qu'il est souvent absent au Cameroun.
- **Ville** : obligatoire.
- **Pays** : le pays courant, sans saisie.

**À l'enregistrement** :
- si une adresse identique existe déjà, l'offre la reprend. « Identique » veut dire même rue, même code postal et même ville, sans tenir compte de la casse ni des espaces : c'est l'empreinte `fingerprint` déjà en place ;
- sinon, l'adresse est **créée** ;
- l'adresse n'est **jamais** ajoutée au carnet d'adresses du compte, et le carnet ne sert plus au formulaire.

Ce qui ne change pas :
- **Modifier une offre** : la rue, le code postal et la ville sont préremplis avec son adresse. Si on les change, on revient au même principe (reprise ou création).
- **Fiche** : elle n'affiche toujours que la ville, jamais la rue.

### Générique

- **Suggestions sur un champ** : un champ texte peut recevoir des suggestions servies par une route (`Field::text('street')->suggestions('api.v1.addresses.suggestions')`). Chaque suggestion porte son texte et les autres champs à remplir (`fill: { zip, city }`). L'app les affiche sous la saisie, sans rien connaître des adresses.
- **Route des suggestions** : `GET /api/v1/addresses/suggestions?q=…`.
  - Elle est réservée à un membre connecté, dans le pays courant.
  - Elle demande au moins 3 caractères et répond 10 adresses au plus, sans doublon.
  - Elle est limitée en nombre d'appels.
  - Elle ne renvoie que la rue, le code postal et la ville : jamais un nom, un compte ou un identifiant.

### Risque assumé (à consigner)

Toutes les adresses du pays sont proposées, y compris les adresses personnelles des carnets des membres. Un membre qui tape le début d'une rue peut donc voir des adresses réelles d'autres membres (rue, code postal, ville), sans savoir à qui elles appartiennent.

Ce qui limite le risque :
- un compte connecté est exigé ;
- il faut au moins 3 caractères ;
- 10 résultats au plus ;
- le nombre d'appels est limité ;
- aucune donnée de la personne n'est servie.

Si ce risque devient gênant, il suffira de restreindre la recherche aux adresses des offres. Je l'avais recommandé ; ce serait un seul changement, dans la requête.

### Back-office

Inchangé : il garde le choix d'une adresse existante du pays de l'offre. Le saisir comme dans l'app serait un chantier à part.

### Hors périmètre

- La géolocalisation, une carte ou un service externe d'adresses (Google, OpenStreetMap).
- Le quartier comme champ à part.
- La fusion des adresses en double déjà présentes.

## Découpage

1. **Serveur** :
   - `Field::suggestions()` et son format servi ;
   - la route des suggestions ;
   - dans `OfferForm`, l'adresse en trois champs (rue, code postal, ville) au lieu du choix ;
   - dans `OfferService`, la reprise ou la création de l'adresse par son empreinte ;
   - textes, OpenAPI et tests.
2. **App** : un champ texte qui affiche les suggestions et remplit les autres champs ; tests.
3. **Vérification** au simulateur, puis QA indépendante.

## Critères de succès

- **Suggestions** : en tapant « Njo » dans la rue, le propriétaire voit les adresses existantes de cette rue. En choisir une remplit le code postal et la ville.
- **Reprise d'une adresse** : enregistrer avec une adresse existante (même rue, même code postal, même ville, casse et espaces ignorés) reprend cette adresse, sans en créer une deuxième.
- **Création** : enregistrer avec une adresse nouvelle la crée dans le pays courant. Le carnet d'adresses du compte reste inchangé.
- **Champs requis** : rue et ville sont obligatoires, le code postal non.
- **Route des suggestions** :
  - elle refuse un visiteur (401) ;
  - elle ne répond rien sous 3 caractères ;
  - elle ne sert que rue, code postal et ville, du pays courant, 10 au plus.
- **Modification** : la rue, le code postal et la ville sont préremplis ; les changer reprend une adresse existante ou en crée une.
- **Tests** : les suites serveur et app passent, ainsi que tsc et le lint.

## Ce que j'ai décidé seul

- **Réutilisation** du service d'adresses du back-office :
  - les suggestions passent par `AddressService::suggest()`, la même recherche, triée par rue ;
  - l'enregistrement passe par `AddressService::findOrCreate()`, une adresse unique par pays et par empreinte.

  Rien n'a été recopié.
- **Décisions remplacées** : en codant, j'ai découvert que trois décisions du 2026-11-18 avaient **retiré** les suggestions de l'app puis du serveur. Le contrat validé les rétablit, pour l'adresse d'une offre seulement. Les trois décisions portent une ligne « Remplacée en partie par » ; le carnet d'adresses reste sans suggestions.
- **Format des suggestions** : une ressource typée (`AddressSuggestionResource`) pour la documentation de l'API, qui sert `value`, `label` et `fill: {zip, city}`.
- **Code postal vide** : il est enregistré comme une chaîne vide (la colonne n'accepte pas `null`), et l'empreinte en tient compte.
- **Ancien envoi** : une offre modifiée sans les champs d'adresse garde son adresse. Une rue ou une ville vide retire l'adresse (seulement si le domaine ne l'exige pas).
- **Champ à suggestions dans l'app** : la liste se place sous la saisie, après 300 ms d'attente et dès 3 caractères. Elle disparaît une fois un choix fait, jusqu'à la frappe suivante ; un échec n'affiche rien.

## Ce qui a été fait

- **Serveur** :
  - `Field::suggestions()`, avec la clé `suggestions` servie ;
  - `GET /api/v1/addresses/suggestions` (connecté, 60 appels par minute, 3 caractères, 10 résultats, pays courant) ;
  - `OfferForm` en rue, code postal et ville (l'étape « Adresse et catégorie ») ;
  - `OfferService` reprend ou crée l'adresse ;
  - textes fr/en et `openapi.json` exporté sans erreur.
- **Tests serveur** :
  - reprise ou création, champs requis, carnet non touché ;
  - suggestions : 401, moins de 3 caractères, pays, forme servie, 10 au plus ;
  - préremplissage à la modification ;
  - deux tests du carnet et un test de l'espace pays mis à jour.

  La suite complète passe.
- **App** :
  - `TextField` avec ses suggestions ;
  - `onFill` câblé dans `FormField` et `DynamicForm` ;
  - `FormService.suggestions()`, qui refuse un point d'API d'une autre forme.

  218 tests passent, ainsi que tsc et le lint.
- **Simulateur** :
  - à l'étape 3, taper « Rue » propose « Rue de Bastos — 00237 Yaoundé », « Rue de Kotto… » et « Rue Njo-Njo… » ;
  - choisir la première remplit « 00237 » et « Yaoundé », puis la liste se ferme.
- **QA indépendante** : GO, 0 bloquant.

## Ce que je n'ai pas vérifié

- **Simulateur** : l'enregistrement final de l'offre (je ne l'ai pas fait, pour ne pas modifier la démo) ; les tests le couvrent.
- **Tests absents** : la limite d'appels (429) et l'usage d'un autre `X-Country`.
- **Navigateur** : le back-office, inchangé.

## Ce qui reste en suspens

- **Risque assumé** : toutes les adresses du pays sont suggérées, y compris celles des carnets des membres. Pour revenir en arrière, il suffit de restreindre la requête aux adresses des offres.
- **Back-office** : la saisie d'adresse d'une offre y reste un choix parmi les adresses existantes.
