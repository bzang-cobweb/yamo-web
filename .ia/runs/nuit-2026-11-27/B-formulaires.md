# Nuit du 2026-11-27 — Lot B, formulaires (B8, B9, B10, B11)

Contrat : `specs/2026-11-27-visiteur-profil-favoris.md`. Mode nuit, sans question à l'humain.

## Fait

- **B8 Notifications** : lien « Notifications » dans le menu principal, après « Confidentialité », icône `bell` /
  `notifications`. Formulaire `auth.profile.notifications` (`PATCH /api/v1/auth/me/notifications`) : « Activité du
  compte » et « Rappels » (cases e-mail, SMS, push, toutes cochées par défaut), « Offres » et « Nouveautés »
  (interrupteurs, oui par défaut). Colonne JSON `users.notification_settings` (vide = défauts), clés et défauts dans
  `Modules\Auth\Support\NotificationSettings`, `User::notificationSettings()`. **Câblé** : `User::notificationChannels()`,
  point central de toutes les notifications (Offer, Partner), ne garde que l'e-mail et le SMS cochés dans « Activité du
  compte ». Les codes de vérification n'y passent pas.
- **B9 Vérification d'identité** : `auth.identity` commence par la note « Nous ne partageons pas ces informations. »
  (note du premier champ), puis le pays de délivrance (pays actifs, cherchable), le type de pièce (radio : permis de
  conduire, passeport, carte d'identité), puis les photos. Les trois sont obligatoires. Colonnes
  `users.identity_country_id` (FK `countries`, nullOnDelete) et `users.identity_document_type`
  (`IdentityDocumentType`), écrites par `IdentityDocumentService::submit()`. Back-office : l'onglet « Identification »
  montre le pays et le type.
- **B10 Adresse de résidence** : lien dans « Informations personnelles », après le mot de passe, icône `house` / `home`.
  Formulaire `auth.profile.residence` (`PUT /api/v1/auth/me/residence`), champs repris d'`UserAddressForm`
  (extraits dans `UserAddressForm::addressFields()`), sans destinataire. Colonne `user_addresses.is_residence`
  (défaut faux), `User::residence()`, `UserAddressService::saveResidence()` (crée ou modifie). Absente de
  « Mes adresses » et non ouvrable par `auth.address`.
- **B11 Contact d'urgence** : lien dans « Informations personnelles », après la résidence, icône `cross.case` /
  `emergency`. Table `emergency_contacts` (user_id unique, cascade), modèle `EmergencyContact`,
  `User::emergencyContact()`. Formulaire `auth.profile.emergency-contact` (`PUT /api/v1/auth/me/emergency-contact`) :
  nom (requis), lien de parenté, e-mail (facultatif), téléphone (`Field::phone`, requis, E.164 par `PhoneNumbers`),
  langue (requise, `Locales::options()`). Crée ou modifie.
- Ordre du sous-menu « Informations personnelles » : mes informations, e-mail, téléphone, mot de passe, adresse de
  résidence, contact d'urgence, vérification d'identité.
- Mobile : **aucun changement** (tous les types de champs existent déjà dans `FormField.tsx`).

## Preuves

- `ddev exec php artisan test --compact Modules/Auth` : 274 passed (1529 assertions).
- `ddev exec php artisan test --compact` (suite complète) : **972 passed** (6229 assertions), aucun échec.
- `ddev exec php artisan migrate` : les 4 migrations `2026_12_08_111000` à `114000` jouées ; `migrate:status` : Ran
  (lot 88). `migrate:rollback --step=4 --pretend` : les `down` suppriment bien colonnes, clé étrangère et table.
- `ddev exec vendor/bin/pint --dirty` : PASS.
- Nouveaux tests : `NotificationSettingsApiTest`, `ResidenceApiTest`, `EmergencyContactApiTest` ; complétés :
  `IdentityApiTest`, `UserIdentityTest` (Filament), `ProfileMenuApiTest`, `ProfileFormSubmissionApiTest`. Chacun
  couvre lien et icône, affichage prérempli (défauts puis valeurs enregistrées), enregistrement, validation, refus sans
  connexion.

## Commits (web, branche `nuit/2026-11-27`)

- `653a97b` Profil : lien « Notifications »… ; décision
- `15d8185` Vérification d'identité : une note, le pays de délivrance et le type de pièce… ; décision
- `6c15f5c` Profil : « Adresse de résidence » dans « Informations personnelles »… ; décision
- `93c699c` Profil : « Contact d'urgence » dans « Informations personnelles »… ; décision

Décisions : `.ia/context/decisions/2026-11-27-notifications-du-profil.md`,
`2026-11-27-identite-pays-et-type-de-piece.md`, `2026-11-27-adresse-de-residence.md`, `2026-11-27-contact-d-urgence.md`.

## Décidé seul

- B8 : un canal de compte désactivé (`AuthChannels`) n'est pas proposé, le push l'est toujours ; « Rappels »,
  « Offres », « Nouveautés » et le push sont **enregistrés seulement** (aucun envoi ne les lit encore) ;
  l'invitation à laisser un avis (module Offer, interdit cette nuit) suit « Activité du compte » ; une clé non envoyée
  ne change pas le choix.
- B9 : type de pièce en radio ; les trois champs obligatoires ; pays prérempli (déjà choisi, sinon pays du compte,
  sinon pays courant) ; un seul couple pays/type par compte, remplacé à chaque envoi ; la note d'état reste la note du
  formulaire, la note « ne partageons pas » est celle du premier champ.
- B10 : région parmi celles du pays courant (comme « Mes adresses ») ; destinataire = prénom et nom du compte ; note
  « pas montrée aux autres membres » ; au back-office elle apparaît parmi les adresses sans marque distincte.
- B11 : téléphone non vérifié par code (numéro d'un tiers) ; pays du téléphone = pays courant d'office, langue = celle du
  compte ; lien de parenté et e-mail vides enregistrés comme absents ; pas de lecture au back-office ni de suppression.
- Migrations nommées `2026_12_08_111000…114000` (écart avec l'exemple `110000` pour éviter une collision avec une autre
  session).
- Retouche annexe : dans `UserAddressService`, le docblock de `detach()` était posé sur `address()` ; remis en place.

## Non vérifié

- Rendu dans l'app (simulateur non touché) : les quatre formulaires n'ont pas été vus à l'écran ; aucun test Jest/tsc
  lancé (aucun fichier mobile modifié).
- La langue « de » est dans `app.supported_locales` mais refusée par le champ langue en test : `Locales` lit d'abord
  `config('form.locales')` ; même liste que « Préférences », non creusé.
- Le contact d'urgence et la résidence ne sont pas montrés au back-office.
