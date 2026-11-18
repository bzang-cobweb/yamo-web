# `2026-11-18` — UserAddress déplacé dans le module Auth ; formulaire et liste renommés `auth.*`

- **Type** : décision
- **Question posée** :
  - valider le contrat `specs/2026-11-18-useraddress-dans-auth.md` ;
  - renommer le formulaire et la liste de l'app (option A) ou garder `location.*` (option B).
- **Réponse** : « Yes, approved (A) ».
- **Ce qui est retenu** :
  - **`Address` reste dans Location.** Location garde le modèle, sa policy, `UniqueAddress`, le relation manager des pays et `AddressService` (trouver, créer et suggérer une adresse) ;
  - **`UserAddress` passe dans Auth**, avec ce qui s'y rapporte : modèle, factory, policy, contrôleur, requête, routes, formulaire et liste de l'app, `UserAddressService` (ajouter, modifier, retirer), textes du destinataire et de la liste, tests ;
  - le formulaire et la liste de l'app deviennent `auth.address` et `auth.addresses` ;
  - la migration qui renomme `address_user` en `user_addresses` est déplacée dans Auth sans changer de nom de fichier ;
  - `Address::userAddresses()` est retirée.
- **Motif** : le lien d'un compte à une adresse appartient au compte. Les dépendances ne vont plus que d'Auth vers Location.
- **Portée** : modules Auth et Location ; noms publics du formulaire et de la liste.
- **Corollaires** :
  - les URL, les noms de routes, la base et le back-office ne changent pas ;
  - l'app reçoit les nouveaux noms du serveur et n'a pas à changer.
- **Tranché par** : humain
