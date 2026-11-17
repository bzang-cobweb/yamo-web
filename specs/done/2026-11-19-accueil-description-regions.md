# Contrat — Page d'accueil, description du profil, nombre de régions

- **Chantier** : `2026-11-19-accueil-description-regions`
- **Demande** (chat, 4 points) :
  1. finalement annuler l'alignement vertical pour la page de login ;
  2. ajouter un champ description (textarea, pas traduisible) — précisé : sur le
     modèle User, dans les informations personnelles ;
  3. afficher le nombre de régions d'un pays dans la vue de liste ;
  4. une page d'accueil avec le logo (ou le nom s'il n'y a pas de logo) de
     l'application centré (données Laravel), une description de l'application
     (traduisible, éditable dans le back-office), un bouton de connexion et un bouton
     de création de compte.
- **Règle permanente** : chaque point est livré côté Laravel **et** côté app.
- **Statut** : validé le 2026-11-19, avec un ajout : nombre de caractères du type
  textarea (décision `2026-11-19-contrat-accueil-description-regions.md`)

## Ce que j'ai compris

### 1. Connexion alignée en haut (app)

L'écran de connexion n'est plus centré verticalement : comme les autres écrans
invités, son contenu commence en haut. (Annule la retouche du commit mobile `53c8c0a`.)

### 2. Description de l'utilisateur

- Nouvelle colonne `users.description` (texte, nullable, non traduisible), par une
  nouvelle migration.
- Formulaire « Informations personnelles » (API + app) : champ `description` de type
  textarea, facultatif, après la région, max 1 000 caractères (défaut du type).

### 2 bis. Nombre de caractères du type textarea (ajout à la validation)

- Un champ textarea a une propriété **nombre maximal de caractères**, **1 000 par
  défaut**, modifiable par champ (`Field::textarea('x')->maxLength(500)`).
- Le serveur la valide (`max:N`) sans qu'on l'écrive dans les règles du champ.
- L'API l'envoie avec la définition du champ (`max_length`) ; l'app bloque la saisie
  au-delà et affiche le compteur « n / N ».
- Exposée par `GET /me` (ressource utilisateur) et enregistrée par
  `PATCH /me/personal-info`.
- Back-office : champ textarea dans la fiche utilisateur.
- L'app l'affiche dans le formulaire (le composant textarea existe déjà).

### 3. Nombre de régions dans la liste des pays (back-office)

La table des pays affiche une colonne **Régions** avec le nombre de régions de chaque
pays (compté en base, triable, sans requête par ligne).

### 4. Page d'accueil

**Réglages de l'application (back-office)** — sans nouvelle dépendance :
- Une page Filament **Application** (groupe Paramètres) : téléversement du **logo**
  (image, media library, facultatif) et **description** de l'application en fr/en
  (traduisible, sélecteur de langue comme les autres ressources traduites).
- Stockage : une table `settings` (une ligne unique) avec un modèle traduisible ; le
  logo est un média attaché à cette ligne.
- Accès réservé à une permission déclarée dans le code (comme les autres pages).
- Le **nom** est `config('app.name')` (APP_NAME), non éditable.

**API** : `GET /api/v1/app` (public) renvoie `{ name, logo_url | null, description }`,
description dans la langue de la requête (repli sur la langue par défaut).

**App mobile** : un écran **Accueil**, premier écran d'un invité (avant la connexion) :
- logo centré (ou nom de l'application s'il n'y a pas de logo) ;
- description en dessous ;
- bouton **Se connecter** → écran de connexion ; bouton **Créer un compte** → écran
  d'inscription (avec leur en-tête de retour vers l'accueil).
- Sans en-tête de retour lui-même.

**Web** : la page `/` (aujourd'hui la page « welcome » de Laravel) affiche le logo
(ou le nom) centré et la description ; pour les boutons, voir *Questions*.

## Critères de succès

1. Dans le simulateur, l'écran de connexion a son contenu en haut.
2. Un utilisateur peut saisir, modifier et effacer sa description depuis l'app ; elle
   est relue par `GET /me` et visible dans le back-office ; plus de 1 000 caractères
   est refusé avec un message.
3. La liste des pays montre le nombre exact de régions de chaque pays.
4. Un administrateur change le logo et la description (fr et en) dans le
   back-office ; l'app (accueil) et `GET /api/v1/app` reflètent le changement dans la
   langue de l'utilisateur ; sans logo, le nom de l'application s'affiche.
5. Un invité qui ouvre l'app arrive sur l'accueil et atteint la connexion et
   l'inscription par les deux boutons ; le retour depuis ces écrans ramène à l'accueil.
6. Tests (web + mobile), types, lint et Pint au vert ; OpenAPI régénéré.

## Questions (défaut appliqué sans réponse)

- **Boutons de la page web `/`** : il n'y a pas de connexion/inscription web pour les
  utilisateurs. Défaut : la page web montre logo, nom et description, **sans** les deux
  boutons ; ils n'existent que dans l'app.
- **Taille max de la description utilisateur** : tranché — propriété du type textarea,
  1 000 par défaut.

## Dépendances

Aucune nouvelle dépendance (media library et translatable déjà présents).

## Ce que j'ai décidé seul

- **Réglages dans `app/`** (hors module) : modèle `Setting` (une ligne, table `settings`),
  ressource Filament `SettingResource` à une seule page (`/admin/application`) qui
  édite cette ligne, policy `SettingPolicy`, permission `settings.update` déclarée dans
  `PermissionEnum`. La page réutilise `EditRecord` + le sélecteur de langue existant.
- **Logo** : PNG, JPEG, WebP ou SVG, 2 Mo max, servi tel quel (pas de conversion).
- **Accueil de l'app** : logo/nom et description au centre de l'espace libre, boutons
  en bas. La connexion reçoit à son tour un en-tête « Se connecter » (titre du
  formulaire masqué) pour revenir à l'accueil.
- **Liens connexion ⇄ inscription** : `replace` au lieu de `push`/`back`, pour que le
  retour ramène toujours à l'accueil.
- **Accueil premier écran** : ancre de la pile racine (`unstable_settings.anchor`).
- **Page web `/`** : feuille de style intégrée, sans Vite ; la page « welcome » de
  Laravel et `ExampleTest` sont supprimés (remplacés par `ApplicationTest`).
- **Colonne Régions** des pays : elle existait, masquée par défaut ; elle est rendue
  visible.
- **Fabrique utilisateur** : description `null` (une valeur aléatoire rendait les
  tests de JSON exact instables).

## Ce qui a été fait

- Web : textarea `maxLength` (1 000 par défaut, règle `max`, `max_length` dans l'API) ;
  `users.description` (migration, formulaire, service, `UserResource`, fiche
  back-office) ; colonne Régions visible ; réglages + `GET /api/v1/app` + page `/`.
  188 tests, Pint propre, OpenAPI régénéré.
- App : connexion en haut avec en-tête ; compteur et limite du textarea ; type
  `User.description` ; fonctionnalité `welcome` (service, hook, écran, route) ;
  82 tests, tsc et eslint propres.
- Simulateur (compte jetable, supprimé ensuite) : accueil au lancement (nom +
  description), aller-retour connexion/inscription ⇄ accueil, connexion par code,
  compteur 19 / 1000 et description enregistrée, lancement connecté sur les onglets,
  déconnexion vers l'accueil.
- QA indépendante : GO (`.ia/runs/2026-11-19-accueil-description-regions/qa-check.md`).

## Ce que je n'ai pas vérifié

- Le rendu de la page Filament « Application » dans un navigateur (couverte par les
  tests Livewire seulement) et l'affichage d'un vrai logo dans l'app et sur `/`.
- Le contrat OpenAPI ne décrit pas les attributs des champs de formulaire (items
  `{}`) : `max_length` n'y apparaît pas, pas plus que les autres attributs — limite
  antérieure à ce chantier.
- Incident en fin de chantier : `APP_NAME` du `.env` local, changé en cours de route
  avec une espace et sans guillemets, rendait toute l'API en 500 (« les formulaires ne
  se chargent plus »). Valeur remise entre guillemets par l'outil env (même valeur) ;
  API de nouveau en 200, OpenAPI régénéré sans écart, 188 tests au vert.

## Ce qui reste en suspens

- Typage des champs de formulaire dans OpenAPI (si souhaité).
