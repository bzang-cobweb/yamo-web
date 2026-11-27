# Nuit du 2026-11-27 — Lot B, plusieurs numéros de téléphone (B1)

Contrat : `specs/2026-11-27-visiteur-profil-favoris.md`. Demande : « Possibilité de rajouter plusieurs numéros de
téléphone ». Mode nuit, sans question à l'humain. Décision : `.ia/context/decisions/2026-11-27-plusieurs-numeros.md`.

## Fait

- **Données** : table `user_phones` (user_id, phone E.164, phone_country_id, verified_at, timestamps ; unique
  (user_id, phone), index sur phone), modèle `UserPhone`, relation `User::phones()`. Le principal reste `users.phone`.
- **Ajout** : formulaire `auth.profile.phones.create` (pays + numéro, « Envoyer le code ») → `POST /auth/me/phones` (202,
  défi, purpose `add_phone`, compte connecté exigé) → code SMS au numéro → `POST /auth/codes/verify` ajoute le numéro,
  vérifié, et rend le compte. Service `UserPhoneService`, règle `AvailableExtraPhone`.
- **Refus** : numéro principal du compte (« C'est déjà votre numéro principal. »), déjà dans sa liste (« Ce numéro est déjà
  dans vos numéros. »), principal ou supplémentaire d'un autre compte (`validation.unique`) ; revérifié à la validation
  du code (erreur sur `code`). Un compte inactif cède son numéro supplémentaire, comme le principal (`PhoneOwnership`).
- **Retrait** : `DELETE /auth/me/phones/{id}`. **Rendre principal** (fait, simple) : `POST /auth/me/phones/{id}/primary`,
  échange avec `users.phone` (l'ancien principal rejoint la liste) ; refusé si un autre compte a ce numéro comme
  principal. Numéro d'un autre compte : 404 (`UserPhonePolicy`).
- **Profil** : le lien `phone` du sous-menu « Informations personnelles » devient la liste « Mes numéros »
  (`auth.phones`, `UserPhoneListing`) : le principal épinglé en tête (masqué, tag « Principal », « Changer » →
  `auth.profile.phone`, « Vérifier » s'il n'est pas vérifié, « Ajouter » sans numéro), puis les supplémentaires (masqués,
  pays · « non vérifié » s'il y a lieu, « Rendre principal » avec confirmation, « Retirer »), bouton du bas « Ajouter un
  numéro ». Toujours absent si le canal téléphone est coupé (`AuthChannels`). « Changer de numéro » revient à la liste.
- `PhoneChangeService` : un numéro supplémentaire devenu principal quitte la liste.
- **Back-office** : onglet « Numéros supplémentaires » sur la fiche utilisateur (`PhonesRelationManager`, numéros entiers,
  retrait, pas d'ajout).
- « Mes données » exporte les numéros supplémentaires. Textes fr, en, de.
- **Mobile** : aucun changement nécessaire (liste, actions de carte vers un formulaire, bouton du bas, défi de code
  dans `ProfileFormScreen` : tous génériques et déjà en place).

## Preuves

- `ddev exec php artisan test --compact` (suite complète) : **1008 passed** (6499 assertions), aucun échec.
- `ddev exec php artisan migrate` : `2026_12_08_122000_create_user_phones_table` jouée ;
  `migrate:rollback --step=1 --pretend` : `drop table if exists user_phones`.
- `ddev exec vendor/bin/pint --dirty` : passé (une import inutile retirée).
- Nouveaux tests : `UserPhonesApiTest` (13 : liste masquée et ordre, principal non vérifié / absent, ajout par code SMS,
  refus principal / doublon / autre compte principal et supplémentaire / invalide, numéro pris entre code et
  validation, numéro d'un compte inactif libéré, code validé par un autre compte refusé, retrait, rendre principal,
  rendre principal refusé, changer le principal vers un supplémentaire, numéro d'un autre compte 404, sans connexion
  401) ; `UserPhonesRelationManagerTest` (back-office). Adaptés : `ProfileMenuApiTest` (le lien est une liste, libellé
  « Mes numéros »), `ProfileFormSubmissionApiTest` (« Changer de numéro » → `back`, nouveau formulaire).

## Commits

Web (`nuit/2026-11-27`) :
- `382450a` Profil : plusieurs numéros de téléphone… ; « Mes numéros » remplace le lien Téléphone… ; décision
- (ce rapport, commit suivant)

Mobile : aucun.

## Décidé seul

- Principal = carte épinglée (`Listing::pinned()` sur le compte, identifiant `primary`), pas une copie dans `user_phones`.
- Libellé du lien : « Mes numéros » quel que soit le compte (avant : « Ajouter / Changer de numéro »).
- « Changer de numéro » revient à la liste au lieu du profil.
- « Rendre principal » sans nouveau code (prouvé à l'ajout) ; refusé si un autre compte, même inactif, a pris ce numéro
  comme principal.
- Le principal prime : un numéro supplémentaire d'un compte actif n'empêche pas un autre compte d'en faire son principal
  (inscription, changement) ; suite possible : le refuser.
- Numéro supplémentaire retiré d'un compte inactif : sans e-mail à l'ancien titulaire.
- Le retrait au back-office est permis (`users.update`), l'ajout non.

## Non vérifié

- Rendu dans l'app (simulateur non touché) : liste « Mes numéros », boutons « Changer » / « Rendre principal », retour à la
  liste après le code. Après « Rendre principal », le compte gardé par l'app n'est pas rafraîchi tant que `/me` n'est
  pas relu (le profil affiche l'ancien numéro masqué jusque-là).
- Envoi réel du SMS (fournisseur : `LogSmsSender`) ; tests par `FakeSmsSender`.
- Contrat OpenAPI (`ddev composer openapi`) non régénéré.
