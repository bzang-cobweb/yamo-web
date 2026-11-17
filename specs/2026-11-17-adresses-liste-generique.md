# Adresses de l'utilisateur et liste générique

Demande du 2026-11-17 :

> Mettre en place les adresses
> 1. rue, zip et ville, latitude et longitude
> 2. une adresse appartient à un utilisateur ; rajouter la relation manager filament
> 3. ajouter un profile menu link qui dirige vers la liste des adresses
> 4. prévoir une liste générique qui se charge comme les formulaires génériques, avec des items
> 5. la liste générique doit permettre de créer un nouvel item
> 6. possibilité d'éditer et de supprimer un item de la liste

Retour sur la première version du contract, le 2026-11-18 :

> 1. mettre l'adresse plutôt comme relation manager d'un pays (pas de doublon)
> 2. la liste des adresses de l'utilisateur est donc une relation manager qui se fait sur un pivot id de l'utilisateur et id de l'adresse
> 3. à la création d'une adresse, si elle existe déjà on créer juste la relation pivot, autrement on la créé d'abord avant de faire le pivot
> 4. à l'édition d'une adresse, si la nouvelle adresse existe déjà on fait juste la relation pivot, autrement on la créé d'abord avant de faire le pivot.
> 5. validé

## Ce que j'ai compris

Une adresse appartient à un **pays** et n'existe qu'une fois. Plusieurs utilisateurs peuvent partager la même adresse, par exemple un couple. Un utilisateur est relié à ses adresses par un **pivot**. L'utilisateur gère ses adresses depuis son profil dans l'app. Un administrateur les gère depuis la fiche de l'utilisateur et depuis celle du pays.

Dans l'app, l'écran des adresses n'est pas propre aux adresses. C'est une **liste générique décrite par le serveur**, comme les formulaires. Une prochaine liste n'aura donc besoin que du serveur.

Les listes demandent **une nouvelle version de l'app**, une seule fois. Les versions actuelles ignorent un lien du menu sans `form` : elles ne montrent pas « Mes adresses » et ne plantent pas.

### Données (module Location)

- Table `addresses` :

  | Colonne | Type |
  |---|---|
  | `id` | clé |
  | `country_id` | clé étrangère vers `countries`, supprimée avec le pays, comme les régions |
  | `street` | texte, 255 |
  | `zip` | texte, 20 |
  | `city` | texte, 255 |
  | `latitude` | décimal (10,7), facultatif, de −90 à 90 |
  | `longitude` | décimal (10,7), facultatif, de −180 à 180 |
  | horodatage | |

  Index unique sur `(country_id, street, zip, city)`.
- **Pas de doublon.**
  - Deux adresses sont la même si elles ont le même pays, la même rue, le même NPA et la même ville.
  - La comparaison ignore la casse et les espaces en trop : « rue du lac  4 » et « Rue du Lac 4 » sont la même adresse.
  - Les espaces sont nettoyés à l'enregistrement.
- Pivot `address_user` :
  - colonnes `address_id`, `user_id` et horodatage ;
  - unique sur la paire ;
  - supprimé avec l'adresse ou avec le compte purgé.
- Relations :
  - `Address` : `belongsTo(Country)` et `belongsToMany(User)` ;
  - `Country` : `hasMany(Address)` ;
  - `User` : `belongsToMany(Address)`, par ordre de rattachement.
- **Une seule règle**, servie par un `AddressService` et utilisée par l'API comme par le back-office :
  - **rattacher** une saisie à un utilisateur :
    - on cherche l'adresse identique ;
    - si elle n'existe pas, on la crée ;
    - puis on crée le pivot s'il n'existe pas déjà ;
  - **changer** l'adresse d'un utilisateur :
    - on cherche l'adresse identique à la nouvelle saisie, ou on la crée ;
    - on fait pointer le pivot vers elle ;
    - **l'ancienne adresse n'est jamais modifiée**, car d'autres comptes peuvent la partager ;
  - **retirer** une adresse d'un utilisateur : on supprime le pivot ; l'adresse reste dans son pays.
- La policy `AddressPolicy` :
  - via l'API, le titulaire voit, ajoute, change et retire **ses** adresses ; une adresse sans pivot vers lui répond 404 ;
  - dans le back-office, les droits passent par de nouvelles permissions `addresses.view|create|update|delete`, comme pour les régions.

### Back-office

- **Fiche d'un pays** : un relation manager « Adresses (n) ».
  - Colonnes : rue, NPA, ville, latitude, longitude, et le nombre d'utilisateurs.
  - Création, modification et suppression dans une fenêtre.
  - La création et la modification refusent un doublon : l'erreur s'affiche sous la rue.
- **Fiche d'un utilisateur** : un relation manager « Adresses (n) » sur le pivot.
  - Colonnes : rue, NPA, ville, pays.
  - « Ajouter » ouvre un formulaire (pays, rue, NPA, ville, latitude, longitude) qui applique la règle *rattacher*.
  - « Modifier » applique la règle *changer*.
  - « Retirer » supprime le pivot.

### API

- `POST /v1/auth/me/addresses` applique la règle *rattacher*.
- `PATCH /v1/auth/me/addresses/{address}` applique la règle *changer*.
- `DELETE /v1/auth/me/addresses/{address}` applique la règle *retirer*.
- Ces trois points sont validés par le formulaire `location.address` : pays (liste des pays actifs), rue, NPA, ville, latitude et longitude. Une seule source de règles.

### Liste générique (module Form, à côté des formulaires)

- Une classe `Listing` déclare une liste. Le nom vient de ce que `List` est un mot réservé de PHP.
  - Elle est inscrite à un registre, comme les formulaires.
  - Elle est servie par `GET /v1/lists/{list}` : une liste inconnue répond 404 et un invité 401.
- Réponse pour les adresses :

  ```json
  {
    "data": {
      "name": "location.addresses",
      "title": "Mes adresses",
      "empty": "Aucune adresse pour l'instant.",
      "create": { "label": "Ajouter une adresse", "form": "location.address" },
      "items": [
        {
          "id": "12",
          "title": "Rue du Lac 4",
          "subtitle": "1200 Genève, Suisse",
          "edit": { "form": "location.address", "record": "12" },
          "delete": {
            "label": "Retirer",
            "confirm": "Retirer cette adresse ?",
            "method": "DELETE",
            "endpoint": "/v1/auth/me/addresses/12",
            "success": { "action": "back", "message": "Adresse retirée." }
          }
        }
      ]
    }
  }
  ```

  - `create`, `edit` et `delete` sont facultatifs : une liste peut être en lecture seule.
  - Un item n'a que `title` et `subtitle`, pour le moment.
- **Formulaire lié à un enregistrement** :
  - `GET /v1/forms/location.address?record=12` sert le formulaire prérempli avec cette adresse. Son envoi est décrit vers `PATCH /v1/auth/me/addresses/12`.
  - Sans `record`, c'est le formulaire vide, envoyé vers `POST`.
  - Un `record` qui n'est pas une adresse du compte répond 404.
  - Les formulaires existants ignorent `record`.
- **Nouvelle suite** `back` (`SuccessAction::BACK`) : retour à l'écran précédent, la liste, qui affiche le message et se recharge.

### Menu du profil

- Le nouveau cas `ProfileMenuKey::ADDRESSES` produit le lien « Mes adresses » à la suite des liens existants.
- Un lien porte **soit** `form`, **soit** `list` : `{ "key": "addresses", "label": "Mes adresses", "list": "location.addresses" }`.

### App

- La nouvelle route `/profile/lists/[list]` affiche `ProfileListScreen`, un écran générique :
  - il affiche le titre dans l'en-tête, les items (titre et sous-titre), et le texte `empty` quand il n'y a aucun item ;
  - le bouton `create` ouvre `/profile/[form]` ;
  - toucher un item ouvre `/profile/[form]?record=12` ;
  - le bouton « Retirer » d'un item demande confirmation avec `confirm`, envoie `method` et `endpoint`, puis affiche le message et recharge la liste ;
  - la liste se recharge à chaque retour sur l'écran.
- `ProfileFormScreen` accepte `record` et connaît la suite `back`.
- Le menu ouvre `/profile/lists/[list]` pour un lien `list` et `/profile/[form]` pour un lien `form`. Un lien sans l'un ni l'autre est ignoré.
- **Sécurité** : la règle des formulaires vaut pour les listes. L'app n'appelle qu'un endpoint `/v1/…` avec une méthode et une suite connues. Sinon elle affiche une erreur et n'envoie rien.
- Les messages de confirmation restent en mémoire, jamais dans l'URL.

### Textes

En français et en anglais : le menu, le titre de la liste, les libellés des champs, les messages, les permissions et le back-office.

## Découpage

1. **Web, données** :
   - migrations, modèle, relations ;
   - `AddressService` ;
   - policy et permissions ;
   - les deux relation managers ;
   - tests.
2. **Web, API** :
   - formulaire `location.address` avec `record` ;
   - points d'API ;
   - `Listing`, son registre, son point d'API et la liste `location.addresses` ;
   - lien du menu ;
   - tests ;
   - `openapi.json` régénéré.
3. **Mobile** :
   - types et service de liste ;
   - `ProfileListScreen` et sa route ;
   - `record` et `back` dans `ProfileFormScreen` ;
   - liens `list` du menu ;
   - tests.
4. **QA indépendante**, essai dans le simulateur avec un compte jetable `@example.test`, puis clôture.

## Critères de succès

- Une adresse identique (pays, rue, NPA, ville, sans tenir compte de la casse ni des espaces) n'existe jamais deux fois en base.
- Un administrateur gère les adresses d'un pays depuis sa fiche, et un doublon y est refusé avec un message.
- Un administrateur ajoute, change et retire les adresses d'un utilisateur depuis sa fiche, selon les règles *rattacher*, *changer* et *retirer*.
- Dans l'app, « Mes adresses » apparaît dans le menu du profil et ouvre la liste de **ses** adresses, ou le texte « aucune adresse ».
- Depuis cette liste, l'utilisateur :
  - ajoute une adresse : elle apparaît au retour, avec le message ;
    - si l'adresse existe déjà en base, aucune n'est créée et il y est simplement rattaché ;
  - modifie une adresse : le formulaire est prérempli, et la liste montre la nouvelle adresse au retour ;
    - l'ancienne adresse reste intacte pour les autres comptes ;
  - retire une adresse après confirmation : elle disparaît de sa liste, mais pas de la base.
- Une saisie invalide (rue vide, latitude hors bornes) affiche l'erreur sous le champ.
- Un utilisateur ne peut ni lire, ni changer, ni retirer une adresse qui n'est pas la sienne, par l'API comme par le formulaire avec `record` : la réponse est 404.
- Une deuxième liste déclarée uniquement côté serveur (en test) est servie et s'affiche sans code d'app propre.
- Les formulaires du profil existants marchent comme avant.
- Les tests web et mobile passent, Pint, tsc et lint sont propres, et `openapi.json` est à jour.

## Questions tranchées

1. **Latitude et longitude** : saisie à la main, facultatives. C'est le défaut ; une autre façon de les remplir demanderait une dépendance.
2. **Pays** : obligatoire, puisque l'adresse appartient à un pays (retour du 2026-11-18).
3. **Retrait** : un bouton sur chaque item de la liste.
4. **Nombre d'adresses** : sans limite.

## Dépendances

Aucune.
