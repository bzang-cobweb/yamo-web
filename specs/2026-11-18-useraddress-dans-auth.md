# UserAddress déplacé dans le module Auth

Demande du 2026-11-18 :

> Tout ce qui touche à UserAddress doit être déplacé dans le Module Auth

## Ce que j'ai compris

L'**adresse partagée** (`Address`, unique par pays) reste une affaire de **Location**. Le **lien d'un compte à une adresse**, avec son destinataire (`UserAddress`), devient une affaire d'**Auth** : son modèle, ses règles, son API, son formulaire, sa liste et ses textes.

**Sens des dépendances après le déplacement : Auth dépend de Location, et Location ne connaît plus `UserAddress`.**

Aucun comportement ne change : mêmes routes, mêmes réponses, même base, même back-office. Seuls les noms publics du formulaire et de la liste changent (voir « À trancher »).

### Ce qui va dans Auth

| Aujourd'hui (Location) | Demain (Auth) |
|---|---|
| `Models/UserAddress` | `Modules\Auth\Models\UserAddress` |
| `database/factories/UserAddressFactory` | factory du module Auth |
| `Policies/UserAddressPolicy` (inscrite par `LocationServiceProvider`) | policy d'Auth, inscrite par `AuthServiceProvider` |
| `Http/Controllers/Api/AddressController` | `Modules\Auth\Http\Controllers\Api\UserAddressController` |
| `Http/Requests/AddressRequest` | `Modules\Auth\Http\Requests\UserAddressRequest` |
| routes `v1/auth/me/addresses` dans `Location/routes/api.php` | `Auth/routes/api.php` ; les noms `api.v1.auth.me.addresses.*` et les URL ne changent pas |
| `Forms/AddressForm` (formulaire de l'app) | `Modules\Auth\Forms\UserAddressForm` |
| `Lists/AddressListing` | `Modules\Auth\Lists\UserAddressListing` |
| `AddressService::attach()`, `update()` et `detach()`, plus `RECIPIENT_FIELDS` | nouveau `Modules\Auth\Services\UserAddressService`, qui s'appuie sur `AddressService` |
| Filament `AddressForm::recipient()` (genre, prénom, nom) | méthode du relation manager d'Auth, qui est déjà dans Auth |
| textes du destinataire, du formulaire et de la liste de l'app (`location::address.recipient`, `.detach`, `.form.*`, `.list.*`) | `auth::address.*`, en fr et en |
| migration `2026_11_18_120000_turn_address_user_into_user_addresses` | dossier des migrations d'Auth, **fichier inchangé** |
| tests : `Api/AddressApiTest`, et la partie compte de `AddressServiceTest` | tests d'Auth (`UserAddressApiTest`, `UserAddressServiceTest`) |

### Ce qui reste dans Location

- `Address`, sa factory, `AddressPolicy`, `UniqueAddress`, et le relation manager des adresses d'un pays.
- `AddressService`, réduit à l'adresse partagée : `find()`, `findOrCreate()`, `suggest()`, `fromSuggestionLabel()` et `ADDRESS_FIELDS`.
- Le schéma Filament de l'adresse : pays, rue, NPA, ville, coordonnées et suggestions.
- Les libellés des champs de l'adresse (`location::address.street`, etc.), utilisés aussi par Auth.
- La migration `2026_11_18_100000_create_addresses_table`, **intouchée**, puisqu'elle a déjà tourné. Elle crée encore l'ancienne table `address_user`, que la migration déplacée renomme ensuite.

### Ce qui est retiré

- La relation `Address::userAddresses()`, qui n'est utilisée que par un test. Location ne connaît donc plus `UserAddress`. La clé étrangère garde la suppression en cascade côté base.

### Base de données et migrations

- Aucune nouvelle migration et aucun changement de table.
- Une migration déplacée garde son nom de fichier, donc aucune n'est rejouée ni sautée. Je le vérifierai avec `migrate:status` et un `migrate:fresh` en test.

## À trancher

**Noms publics du formulaire et de la liste**, aujourd'hui `location.address` et `location.addresses`. L'app ne les connaît pas d'avance : elle les reçoit du menu du profil et des items de la liste.

- **Option A (recommandée) :** les renommer `auth.address` et `auth.addresses`. Ils suivent ainsi leur module, comme `auth.profile.preferences`, et l'app n'a pas à changer.
- **Option B :** garder `location.*`. Le nom ne dit plus alors à quel module la liste appartient.
- **Sans réponse :** j'applique l'option A.

## Critères de succès

1. Plus aucune référence à `UserAddress`, `user_addresses` ou au destinataire dans `Modules/Location` (code, textes, tests), hors de la migration historique qui crée `address_user`.
2. Les URL, les noms de routes, les réponses JSON (aux noms près, selon l'option retenue) et le back-office se comportent comme avant. Tous les tests existants passent, déplacés.
3. `migrate:status` ne montre aucune migration en attente, et `migrate:fresh` passe sur la base de test.
4. Les tests web passent, Pint est propre et `openapi.json` est à jour. Les tests mobiles passent, avec les noms des fixtures mis à jour si l'option A est retenue.

## Hors périmètre

- Un changement de comportement, de table ou de route.
- Déplacer `Address` ou les permissions `addresses.*`, déjà déclarées dans `Modules/Auth/app/Enums/PermissionEnum.php`.
