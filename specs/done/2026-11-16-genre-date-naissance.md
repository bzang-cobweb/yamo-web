# Contrat — Genre, date de naissance, nom complet et initiales

- **Chantier** : `2026-11-16-genre-date-naissance`
- **Demande** (chat, 2026-11-16) : « ajouter un GenderEnum (male et female), une date de
  naissance optionnelle (champ de type date) à un utilisateur pour Laravel et mobile
  (mettre ces 2 champs dans le formulaire informations personnelles). Le field du
  formulaire Laravel doit avoir une propriété boolean "required" qui indique si le
  champ est obligatoire ou pas. Ajouter des méthodes qui retournent full name et
  initial dans User model et les utiliser dans le mobile. »
- **Arbitrages tranchés** (chat, 2026-11-16) :
  - le genre est **obligatoire** dans le formulaire ;
  - dans l'app, il se choisit par boutons radio ;
  - la date se saisit avec le **sélecteur natif** (dépendance
    `@react-native-community/datetimepicker`) ;
  - côté Filament : la **fiche** utilisateur seulement, pas la liste.
- **Correction de l'humain avant validation** (chat, 2026-11-16) : « enlever les accents
  dans initial ».
- **Statut** : validé le 2026-11-16 (décision `2026-11-16-contrat-genre-date-naissance.md`)

## Ce que j'ai compris

### Propriété `required` des champs — déjà en place

`Field` porte déjà un booléen `required`, `false` par défaut, réglé par `->required()`.
Il est envoyé à l'app dans chaque définition de champ (`"required": true|false`). Il
produit la règle `required` ou `nullable`, et l'app affiche l'astérisque d'après lui.
**Rien à ajouter** : ce chantier s'en sert (genre obligatoire, date facultative), et un
test le vérifie sur ces deux champs.

### Web

- **`GenderEnum`** (`Modules/Auth/app/Enums/GenderEnum.php`) :
  - valeurs `male` et `female` ;
  - libellés traduits « Homme » / « Femme », « Male » / « Female » ;
  - cast du modèle `User`.
- **Migration** : `gender` (chaîne, `null` permis) et `birth_date` (date, `null`
  permis) sur `users`.
  - Les comptes existants restent sans genre. Le genre devient obligatoire au
    prochain enregistrement des informations personnelles.
  - La base accepte `null`, parce que la commande `auth:create-user` et les comptes
    existants n'en ont pas.
- **Nouveau type de champ `date`** dans le module `Form` :
  - valeur `AAAA-MM-JJ` ;
  - règle de format `date_format:Y-m-d`.
- **Formulaire `auth.profile.personal`** :
  - prénom et nom ;
  - **genre** : `radio`, obligatoire, options de `GenderEnum` ;
  - **date de naissance** : `date`, facultative, antérieure à aujourd'hui et
    postérieure au 1er janvier 1900.
  - `PersonalInfoService` enregistre les quatre champs.
- **`User`** : deux accesseurs, `full_name` (existe déjà : « Prénom Nom ») et
  **`initials`** (nouveau). Les initiales sont la première lettre du prénom et du nom,
  en majuscules et **sans accent** (« Élodie Ängel » → « EA », « Çédric Øster » →
  « CO »). Sans prénom ni nom, c'est la première lettre du pseudonyme, elle aussi
  sans accent.
- **`UserResource`** expose `gender`, `birth_date` (`AAAA-MM-JJ` ou `null`),
  `full_name` et `initials`.
- **Filament**, fiche utilisateur : genre (obligatoire, comme dans l'app) et date de
  naissance (facultative), avec les mêmes règles. La liste ne change pas.
- La commande `auth:create-user` reste sans genre ni date.

### Mobile

- **Dépendance** `@react-native-community/datetimepicker` (`npx expo install`).
- **Composant `DateField`** pour le type `date` :
  - il affiche la date choisie, formatée dans la langue de l'app ;
  - le toucher ouvre le calendrier natif, limité à aujourd'hui ;
  - un champ facultatif peut être vidé (bouton « Effacer », texte servi par l'API) ;
  - il s'ajoute au `switch` de `FormField`.
- **Le genre** s'affiche avec le `RadioField` existant.
- **Nom complet et initiales** : `User` gagne `full_name`, `initials`, `gender` et
  `birth_date`.
  - L'avatar affiche `user.initials`, et le profil affiche `user.full_name`.
  - Le calcul local (`initialsOf`, concaténation du prénom et du nom) disparaît : une
    seule source, le modèle Laravel.

## Découpage

1. Web : enum, migration, type `date`, formulaire, service, ressource, modèle,
   Filament, tests → `qa-check` → commit `yamo-web`.
2. Mobile : dépendance, `DateField`, types, avatar et profil, tests → `qa-check` →
   commit `yamo-mobile`.
3. Vérification de bout en bout dans le simulateur iOS, avec un compte de test que je
   crée (pas le tien).

## Critères de succès

1. Dans Informations personnelles, l'app affiche le genre en boutons radio, marqué
   obligatoire, et la date de naissance avec le calendrier natif, sans astérisque.
2. Enregistrer sans genre est refusé, avec un message traduit sous le champ.
   Enregistrer sans date est accepté.
3. Une date future, ou antérieure à 1900, est refusée avec un message traduit.
4. Le genre et la date enregistrés se retrouvent à la réouverture du formulaire, dans
   la base et dans la fiche Filament.
5. L'avatar et le profil de l'app affichent les initiales et le nom complet calculés
   par Laravel (`initials`, `full_name`). Les initiales n'ont pas d'accent
   (« Élodie Ängel » → « EA »). Aucun calcul local ne subsiste.
6. La propriété `required` de chaque champ est envoyée par l'API, et l'app l'affiche
   (astérisque).
7. Tests web et mobile, typage, lint, Pint au vert. `migrate:status` est à jour.
   `openapi.json` est régénéré.

## Hors périmètre

D'autres genres que `male` et `female` ; afficher l'âge ; ajouter le genre et la date
à la liste Filament ou à la commande `auth:create-user`.

---

## Ce que j'ai décidé seul

- **Bornes de la date incluses** (`after_or_equal:1900-01-01`, `before_or_equal:today`).
  Le contrat disait « antérieure à aujourd'hui », mais le calendrier de l'app permet de
  choisir aujourd'hui : la borne doit l'accepter aussi. Une seule source pour la borne
  basse : `PersonalInfoForm::BIRTH_DATE_MIN`, utilisée aussi par Filament.
- **Messages des bornes traduits** par la clé `values` de `lang/<langue>/validation.php`
  (« jour d'aujourd'hui », « 1er janvier 1900 »). Ces fichiers viennent de laravel-lang :
  la clé est à reporter s'ils sont réécrits. C'est noté dans `project.md`.
- **Validation du genre** : la règle `in` des boutons radio suffit, sans `Rule::enum` en
  double.
- **`birth_date` dans `UserResource`** : passe par une méthode typée, parce que Scramble
  ne savait pas typer le cast `date` et refusait l'export.
- **La fabrique de comptes** donne un genre aléatoire et une date facultative.
- **Correction hors périmètre, trouvée par l'essai** : `useFormDefinition` avec
  `gcTime: 0`. Un formulaire rouvert montrait les valeurs d'avant l'enregistrement. Le
  défaut touchait déjà le prénom et le nom : le formulaire reprenait la définition gardée
  en mémoire.
- **Calendrier iOS** : il se replie dès qu'une date est choisie, et « Effacer » est placé
  sous le champ, avant le calendrier.

## Ce qui a été fait

- **Web** (`0597a1d`) :
  - `GenderEnum`, migration, accesseur `initials` (sans accent) ;
  - type de champ `date` ;
  - formulaire Informations personnelles à quatre champs ;
  - `UserResource` avec `full_name`, `initials`, `gender` et `birth_date` ;
  - fiche Filament ;
  - 108 tests, `openapi.json` régénéré.
- **Mobile** (`41d3843`) :
  - `DateField` avec le sélecteur natif ;
  - `User` étendu, avatar et profil branchés sur l'API ;
  - correction du formulaire rouvert ;
  - 51 tests, README.
- **Simulateur iOS**, avec un compte de test créé puis supprimé (Élodie Ängel) :
  - avatar « EA », nom complet affiché ;
  - genre obligatoire refusé vide, avec un message traduit ;
  - calendrier natif en français, jours futurs grisés ;
  - enregistrement → retour au profil avec confirmation, valeurs en base ;
  - « Effacer » vide la date en base ;
  - formulaire rouvert à jour.
- **Envoi réel sur DDEV** (qa-check web) : bornes, formats, effacement, initiales.
- **qa-check** : GO pour le web (`03-qa-check-web.md`) et GO pour le mobile
  (`04-qa-check-mobile.md`).

## Ce que je n'ai pas vérifié

- **Android** : la boîte de dialogue `DateTimePickerAndroid` a été relue, pas exécutée.
- **Fiche Filament dans un navigateur** : couverte par les tests Livewire seulement.
- **Messages de bornes en anglais** : non rejoués.

## Ce qui reste en suspens

- **Ta session dans le simulateur** a été déconnectée pour l'essai avec le compte de
  test, comme le contrat le prévoyait. Il faut te reconnecter.
- **Gestion des erreurs d'API** : toujours dupliquée entre `DynamicForm`, `FormLoader`
  et `AvatarPicker`.
