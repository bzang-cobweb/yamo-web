# `2026-11-27` — Plusieurs numéros de téléphone : un principal, des numéros supplémentaires

- **Type** : décision
- **Question posée** : « Possibilité de rajouter plusieurs numéros de téléphone » (contrat
  `specs/2026-11-27-visiteur-profil-favoris.md`, B1).
- **Ce qui est retenu** :
  - le numéro principal reste `users.phone` (connexion par SMS, codes, notifications SMS) : inchangé ;
  - les numéros supplémentaires sont dans `user_phones` (user_id, phone E.164, phone_country_id, verified_at ; unique
    (user_id, phone)), modèle `UserPhone`, relation `User::phones()` ;
  - ajouter : formulaire `auth.profile.phones.create` (pays + numéro) → `POST /auth/me/phones` (202, défi ; purpose
    `add_phone`, compte connecté exigé) → code SMS à ce numéro → `POST /auth/codes/verify` ajoute le numéro, vérifié,
    et rend le compte ;
  - refusés (422 sur `phone`, et de nouveau sur `code` à la validation) : le numéro principal du compte, un numéro déjà
    dans sa liste, le numéro principal ou supplémentaire d'un autre compte (un compte inactif le cède, comme pour le
    principal : décision 2026-11-20-restauration-numero-recycle) ;
  - retirer : `DELETE /auth/me/phones/{id}` ; « Rendre principal » : `POST /auth/me/phones/{id}/primary`, le numéro prend
    la place de `users.phone` et l'ancien principal rejoint la liste avec son état vérifié ; celui d'un autre compte : 404
    (`UserPhonePolicy`) ;
  - profil : dans « Informations personnelles », le lien `phone` (« Mes numéros ») ouvre la liste `auth.phones` : le
    principal d'abord (épinglé, masqué, tag « Principal », « Changer » vers `auth.profile.phone`, « Vérifier » s'il ne
    l'est pas, « Ajouter » s'il n'y en a pas), puis les numéros supplémentaires (masqués, « Rendre principal »,
    « Retirer »), bouton « Ajouter un numéro » en bas. Lien présent seulement si le canal téléphone est actif ;
  - back-office : onglet « Numéros supplémentaires » sur la fiche utilisateur (numéros entiers, retrait possible, pas
    d'ajout : il passe par un code SMS) ;
  - « Mes données » exporte aussi les numéros supplémentaires.
- **Décidé seul (mode nuit)** :
  - le principal est une carte épinglée de la liste (`Listing::pinned()` sur le compte lui-même, identifiant
    `primary`) plutôt qu'une ligne copiée dans `user_phones` : une seule source pour le numéro de connexion ;
  - « Changer de numéro » revient désormais à la liste (`back`) au lieu du profil, puisqu'il s'ouvre depuis elle ;
  - « Rendre principal » sans nouveau code : le numéro a été prouvé à l'ajout. Refusé si un autre compte, même inactif,
    l'a pris comme principal depuis (index unique de `users.phone`, et pas de preuve fraîche pour le libérer) ;
  - le principal prime : un numéro supplémentaire d'un compte actif n'empêche pas un autre compte de le prendre comme
    principal (inscription, changement de numéro) ; il reste alors aux deux. Changer son principal pour un de ses propres
    numéros supplémentaires le retire de sa liste ;
  - un numéro supplémentaire retiré d'un compte inactif (numéro recyclé) ne déclenche pas d'e-mail, contrairement au
    principal ;
  - le sous-titre d'une carte est le pays de l'indicatif, suivi de « non vérifié » s'il y a lieu.
- **Hors périmètre / suite possible** : refuser comme principal un numéro supplémentaire d'un autre compte actif ;
  recevoir les codes ou notifications sur un numéro supplémentaire.
- **Portée** : module Auth (migration, modèle, formulaire, liste, service, règle, contrôleur, policy, routes, menu,
  back-office, `PhoneOwnership`, `PhoneChangeService`, `PersonalDataService`) ; app : aucun changement (liste et
  formulaires servis).
- **Tranché par** : humain (demande), session de nuit pour le détail
