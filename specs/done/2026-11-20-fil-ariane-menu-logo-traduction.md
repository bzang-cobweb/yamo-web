# Fil d'Ariane, menu Paramètres, accueil, logo en haut, traduction libre

Demande du 2026-11-20, cinq points, web et mobile.

## Ce que j'ai compris

1. **Fil d'Ariane du back-office**
   - Page d'édition : deux éléments seulement, la liste de la ressource (lien) puis le titre de la fiche. Exemple : `Utilisateurs > test`. Plus de doublon du titre, plus de « Modifier ».
   - Page de liste : aucun fil d'Ariane.
   - Même règle pour la page de consultation (permission), qui est l'équivalent en lecture seule. Les pages de création gardent le fil par défaut (`Utilisateurs > Créer`).
   - La règle vit à un seul endroit, partagé par toutes les ressources.
2. **Menu** : les pays (et leurs régions) quittent le groupe « Localisation » et passent dans le groupe « Paramètres », à côté de « Application ». Le groupe « Localisation » disparaît.
3. **Accueil mobile** : la salutation (« Bonjour … ») est retirée. Le texte `home.greeting` disparaît aussi de l'API.
4. **Logo en haut, mobile** : une barre fixe en haut des écrans connectés (Accueil, Profil et ses formulaires) affiche le logo de l'application. S'il n'y a pas de logo, elle affiche son nom. Ce sont les mêmes données que l'écran de bienvenue (`GET /api/v1/app`).
5. **`t` libre** : `t(key: string, params?: Record<string, string | number>)`.
   - N'importe quelle clé est acceptée, sans liste de clés typée.
   - Les paramètres remplacent les `:nom` dans le texte servi par l'API.
   - La liste `ApiMessageKey` est supprimée.

## Critères de succès

- Sur la fiche d'un utilisateur, d'un rôle ou d'un pays, le fil affiche exactement `<Ressource> > <titre>`.
- Les listes n'affichent aucun fil.
- Le menu ne contient plus de groupe « Localisation ». « Pays » est dans « Paramètres ».
- L'accueil mobile n'affiche plus de salutation.
- Connecté, chaque onglet montre en haut le logo de l'application, ou son nom, et la barre ne défile pas avec le contenu.
- `t('n'importe.quoi', { name: 'x', count: 3 })` compile et remplace `:name` et `:count`. Une clé absente s'affiche telle quelle.
- Tests web et mobile verts. Pint, tsc et eslint propres. OpenAPI à jour si l'API change.

## Ce que j'ai décidé seul

- Le titre d'un pays dans le fil reprend `Country::label()`, nom traduit puis nom natif s'il diffère (`Suisse (Schweiz)`). C'était déjà le titre de sa page.
- Le titre de fiche d'un pays (suppression, recherche) devient ce même libellé, au lieu du code ISO.
- Le groupe « Paramètres » est déclaré une seule fois (`App\Filament\NavigationGroup`), pour que le module Location ne dépende pas d'une clé de langue de l'application.
- Le logo de la barre a une taille fixe (160 × 32, contenu ajusté). Une largeur en pourcentage le rendait invisible.
- Correctif découvert en route : l'adresse du logo dans `GET /api/v1/app` suit désormais l'hôte de la requête, comme l'avatar. En local, l'app joint l'API en HTTP et refusait le certificat HTTPS de DDEV. La règle est partagée par le trait `ServesMediaOnRequestHost`.
- Dans `DynamicForm`, le compteur d'étapes passe des nombres à `t` au lieu de chaînes.

## Ce qui a été fait

- Web :
  - traits `HasRecordTitleBreadcrumbs` (fiches) et `HasNoBreadcrumbs` (listes), branchés sur toutes les ressources ;
  - les pays rangés dans « Paramètres », les fichiers de langue `navigation` du module Location supprimés ;
  - `home.greeting` retiré de l'API ;
  - adresse du logo sur l'hôte de la requête ;
  - tests `BackOfficeNavigationTest` (5) et test d'hôte du logo ;
  - `openapi.json` régénéré.
- Mobile :
  - accueil sans salutation ;
  - barre fixe `AppTopBar` en en-tête des onglets (logo, sinon nom), avec 2 tests ;
  - `t(key: string, params?: Record<string, string | number>)`, les types `ApiMessageKey`, `MessageKey` et `OfflineKey` supprimés ;
  - insets du haut retirés des écrans Profil, désormais sous la barre.
- Vérifications :
  - web : 197 tests, Pint propre ;
  - mobile : 85 tests, tsc et lint propres ;
  - qa-check : GO, 0 bloquant ;
  - simulateur : barre et logo sur Accueil, Profil et un formulaire du profil ; salutation absente.

## Ce que je n'ai pas vérifié

- Le rendu du fil d'Ariane et du menu dans le navigateur : le navigateur intégré refuse le certificat de `yamo.ddev.site`, et en HTTP le back-office ne se charge pas. Seuls les tests couvrent ce point (`getBreadcrumbs()`, `getNavigationGroup()`).
- La barre du haut sans logo (nom de l'application) dans le simulateur, car un logo est réglé. Seul un test couvre ce cas.
- Le mode sombre et Android.

## Ce qui reste en suspens

- Rien pour ce contrat.
