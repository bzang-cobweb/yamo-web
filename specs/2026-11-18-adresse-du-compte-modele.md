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
