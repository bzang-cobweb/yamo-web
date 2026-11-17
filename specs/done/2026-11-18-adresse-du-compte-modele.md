# Adresse du compte : un modèle à part entière, avec destinataire

Demande du 2026-11-18, après le chantier `2026-11-17-adresses-liste-generique` :

> 1. ne pas mettre la colonne comptes sur la liste des adresses
> 2. remplacer le pivot adresse & utilisateur par une Model standard
> 3. ajouter les champs, gender, nom et prénom dans ce Model standard

## Ce que j'ai compris

Le lien entre un compte et une adresse devient une **entrée à part entière**, `UserAddress`, qui porte son destinataire : genre, prénom et nom. L'adresse elle-même (pays, rue, NPA, ville, coordonnées) reste partagée et unique par pays, comme décidé le 2026-11-18.

### Données

- La table `address_user` devient `user_addresses`, par une nouvelle migration : celle qui a déjà tourné n'est pas modifiée.

  | Colonne | Type |
  |---|---|
  | `id` | clé |
  | `user_id` | clé étrangère, supprimée avec le compte purgé |
  | `address_id` | clé étrangère, supprimée avec l'adresse |
  | `gender` | `male` ou `female`, comme le genre du compte |
  | `first_name` | texte, 255 |
  | `last_name` | texte, 255 |
  | horodatage | |

- Les lignes existantes sont gardées. Leur destinataire est rempli avec le genre, le prénom et le nom du compte.
- Le modèle `UserAddress` a `belongsTo(User)` et `belongsTo(Address)`.
  - `User` : `hasMany(UserAddress)`, dans l'ordre de création.
  - `Address` : `hasMany(UserAddress)`.

### Règles, adaptées

- **Ajouter** :
  - l'adresse identique est reprise, ou créée ;
  - puis une `UserAddress` est créée avec le destinataire saisi.
- **Modifier** une `UserAddress` :
  - le destinataire est modifié sur place, puisqu'il n'appartient qu'à ce compte ;
  - si la partie adresse change, l'adresse identique est reprise ou créée, et la `UserAddress` pointe vers elle ;
  - l'ancienne adresse n'est jamais modifiée.
- **Retirer** supprime la `UserAddress`. L'adresse reste dans son pays.
- **L'identifiant** manipulé par l'app et par l'API devient celui de la `UserAddress` :
  - `?record=`
  - `PATCH` et `DELETE /v1/auth/me/addresses/{userAddress}`
  - la `UserAddress` d'un autre compte répond 404.

### Back-office

- **Fiche d'un pays** : la colonne « Comptes » disparaît du tableau des adresses.
- **Fiche d'un utilisateur** : l'onglet « Adresses » liste ses `UserAddress`.
  - Colonnes : destinataire (prénom et nom), rue, NPA, ville, pays.
  - Le formulaire contient genre, prénom, nom, pays, rue, NPA, ville, latitude et longitude.
  - Ajouter, modifier et retirer suivent les règles ci-dessus.

### Saisie de la rue avec suggestions

Ajout du 2026-11-18 :

> dans le formulaire de création ou d'édition de UserAddress, il faut permettre un autocomplete lorsqu'on saisie la rue. si l'adresse existe et qu'on la sélectionne, tous les champs adresse sont remplis

- **Où** : dans le formulaire d'une adresse du compte, à l'ajout comme à la modification, dans le back-office (fiche utilisateur) et dans l'app.
- **Comment** :
  - en tapant la rue, des adresses existantes sont proposées ;
  - en choisir une remplit le pays, la rue, le NPA et la ville ;
  - dans le back-office, la latitude et la longitude sont aussi remplies.
- **Dans l'app**, réponse du 2026-11-18 : « All, with limits ».
  - Les suggestions portent sur toutes les adresses du pays choisi.
  - Elles n'apparaissent qu'à partir de 3 caractères, 5 au plus.
  - **Les coordonnées ne sont pas envoyées.** Choisir une adresse existante laisse donc latitude et longitude vides dans le formulaire de l'app ; à l'enregistrement, l'adresse existante garde les siennes.
  - Aucun nom n'est envoyé : seules les adresses le sont, pas leurs destinataires.
- **Suggestions servies par l'API** : `GET /v1/addresses/suggestions?q=…&country_id=…`. Le formulaire décrit le champ à suggestions, et l'app l'affiche sans le connaître d'avance. C'est un nouveau type de saisie de l'app : il demande **une version de l'app**.

### App

- **Le formulaire `location.address`** commence par genre, prénom et nom.
  - À l'ajout, ces trois champs sont préremplis avec ceux du compte.
- **La liste « Mes adresses »** :
  - titre de l'item : « Jeanne Dupont » ;
  - sous-titre : « Rue du Lac 4, 1200 Genève, Suisse ».
- Hors suggestions, aucun changement de code dans l'app : tout passe par les définitions servies.

## Critères de succès

- La fiche d'un pays n'affiche plus de colonne « Comptes ».
- Une adresse du compte porte son destinataire, et les adresses existantes en ont un, repris du compte.
- Dans l'app et dans le back-office, l'utilisateur peut :
  - ajouter une adresse avec son destinataire ;
  - modifier le destinataire seul, sans toucher l'adresse partagée ;
  - modifier l'adresse, qui est alors reprise ou créée, sans toucher l'ancienne ;
  - retirer une adresse.
- Aucune adresse partagée n'est dupliquée ni modifiée par un compte.
- En tapant au moins 3 caractères de la rue, des adresses existantes du pays sont proposées. En choisir une remplit les champs de l'adresse : coordonnées comprises dans le back-office, sans elles dans l'app.
- La `UserAddress` d'un autre compte répond 404.
- Les tests web et mobile passent, Pint, tsc et lint sont propres, et `openapi.json` est à jour.

Validé le 2026-11-18 (« ok validé »), avec l'ajout des suggestions ; les défauts des questions s'appliquent.

## Questions tranchées

1. **Genre, prénom et nom : obligatoires ?**
   - **Défaut : oui, les trois**, préremplis avec ceux du compte à l'ajout.
   - Autre option : facultatifs.
2. **Même adresse deux fois pour un compte**, avec deux destinataires différents (par exemple soi-même et un parent au même domicile) ?
   - **Défaut : autorisé.** Il n'y a plus d'unicité compte et adresse : deux `UserAddress` peuvent viser la même adresse.
   - Autre option : une seule entrée par compte et par adresse ; la seconde serait refusée avec un message.
3. **Nom du modèle et de la table** : `UserAddress` et `user_addresses`, dans le module Location. **Défaut appliqué.**

## Dépendances

Aucune.

## Ce que j'ai décidé seul

- **Migration plutôt que nouvelle table** : `address_user` est renommée `user_addresses`, ses lignes sont gardées et leur destinataire est repris du compte. Un index simple sur `address_id` remplace l'unicité, que MariaDB exige pour la clé étrangère.
- **Colonnes en base** : `gender` est facultatif, car un compte peut ne pas avoir de genre au moment de la reprise. `first_name` et `last_name` sont vides par défaut. Les formulaires exigent les trois.
- **Relation** : `User::addresses()` renvoie désormais les `UserAddress`, et `Address::users()` disparaît au profit de `Address::userAddresses()`.
- **Permissions** :
  - dans le back-office, ajouter une adresse à un utilisateur demande `addresses.create`, la modifier ou la retirer `addresses.update` ;
  - côté API, `AddressPolicy` ne permet que la lecture des suggestions, et les adresses du compte passent par `UserAddressPolicy`.
- **Suggestions du back-office** :
  - le champ rue propose une liste du navigateur (`datalist`), mise à jour pendant la saisie ;
  - une suggestion a pour libellé « Rue — NPA Ville » ;
  - choisir ce libellé remplit pays, rue, NPA, ville, latitude et longitude ;
  - sans pays choisi, la recherche porte sur tous les pays.
- **Suggestions de l'API** :
  - 30 recherches par minute au plus ;
  - la recherche porte sur une partie de la rue, sans tenir compte de la casse ;
  - les caractères `%` et `_` sont cherchés tels quels.
- **Suggestions de l'app** :
  - le champ texte à suggestions est générique : le serveur peut l'utiliser pour d'autres champs ;
  - la suggestion choisie remplit tous les champs modifiables qu'elle nomme, et ignore ceux en lecture seule, verrouillés, cachés ou inconnus ;
  - une erreur (réseau, limite de débit) n'affiche simplement rien.
- **Point d'API contrôlé** : `isApiPath` est partagé dans l'app, entre les envois et les suggestions.
- **Liste « Mes adresses »** : le titre est le destinataire, le sous-titre « rue, NPA ville, pays ».

## Ce qui a été fait

- **Contrat et décision** (web `e380e08`). Le contrat a été validé avec l'ajout des suggestions, et la portée des suggestions dans l'app a été tranchée par l'humain : « All, with limits ».
- **Web** (`106ddb1`, puis ce commit) :
  - migration ; modèle et factory `UserAddress` ; `UserAddressPolicy` ;
  - `AddressService` réécrit : ajouter, modifier, retirer, suggérer ;
  - colonne « Comptes » retirée de la fiche du pays ;
  - onglet « Adresses » de la fiche utilisateur sur `UserAddress`, avec le destinataire et la rue à suggestions ;
  - `GET /v1/addresses/suggestions` ;
  - `Field::suggestions()` ;
  - formulaire `location.address` avec le destinataire ;
  - liste « Mes adresses » ;
  - `openapi.json` régénéré.
- **Mobile** (`ba61562`, puis ce commit) : types `FieldSuggestions`, `SuggestionList`, câblage dans `TextField`, `FormField` et `DynamicForm`, `fetchSuggestions`, `isApiPath`, tests, README.
- **Résultats** :
  - web : 308 tests et Pint propres ;
  - mobile : 150 tests, tsc et lint propres ;
  - QA indépendante : GO, avec 3 mineurs (`.ia/runs/2026-11-18-adresse-du-compte-modele/01-qa-check.md`), dont deux docblocks corrigés ici.
- **Migration jouée sur MariaDB**, puis vérifiée avec une ligne temporaire :
  - retour arrière, insertion d'un pivot, migration ;
  - le destinataire a bien été repris du compte ;
  - la ligne a été supprimée ensuite.
- **Essai sur le site local** (dans le conteneur DDEV), avec un compte jetable `@example.test` supprimé ensuite avec ses données :
  - le formulaire vide est prérempli avec le destinataire du compte et annonce les suggestions ;
  - la recherche est refusée à 2 caractères (422), et à 3 caractères elle trouve l'adresse ;
  - la même adresse avec un second destinataire est reprise, sans doublon ;
  - changer l'adresse laisse l'autre entrée sur l'adresse d'origine ;
  - après retrait, le formulaire de l'entrée répond 404.

## Ce que je n'ai pas vérifié

- **L'écran de l'app** (liste de suggestions, remplissage) dans le simulateur : vérifié seulement par jest.
- **Les suggestions du back-office dans un navigateur** : le test Livewire vérifie le remplissage, mais pas l'affichage de la liste du navigateur ni son comportement selon les navigateurs (Safari affiche les `datalist` différemment).
- **Aucun test automatique** pour la migration (reprise du destinataire) ni pour l'absence de la colonne « Comptes ». La migration a été vérifiée à la main sur MariaDB.
- **Une entrée d'adresse du super-admin** (« Obam jacky ») a été créée sur la base locale à 23:49 UTC, après mon nettoyage. Elle ne vient pas de mes scripts : je l'ai laissée.

## Ce qui reste en suspens

- **Il faut une nouvelle version de l'app** pour le champ à suggestions : les versions installées affichent la rue sans suggestions, en saisie libre.
- **Risque assumé** : un compte connecté peut découvrir quelles adresses existent dans un pays (rue, NPA, ville), dans la limite de 3 caractères, 5 résultats et 30 recherches par minute.
- **Les permissions `addresses.*`** ne sont toujours données à aucun rôle.
- **Rien n'est poussé.**
