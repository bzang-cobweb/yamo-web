RAPPORT QA — 2026-11-20-fil-ariane-menu-logo-traduction
Go : oui

Base du verdict (tout rejoué par moi) :
- web `ddev artisan test` : 197 passés (929 assertions), 0 échec. `ddev exec vendor/bin/pint --test` : PASS (266 fichiers).
- openapi.json : `scramble:export` vers un fichier jetable, comparé (json.tool) à openapi.json : identique. Fichier jetable supprimé.
- mobile `npx jest` : 22 suites, 85 tests passés. `npx tsc --noEmit` : code 0, sans sortie. `npx expo lint` : aucune erreur ni avertissement.
- Lecture du diff des deux dépôts (hors fichiers humains listés).

Critères :
1 OK. Trait App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs (ressource puis getRecordTitle()) utilisé par EditUser, EditRole, EditCountry, ViewPermission. CountryResource::getRecordTitle() renvoie Country::label(). BackOfficeNavigationTest vérifie le tableau exact (ex. « Suisse (Schweiz) »).
2 OK. HasNoBreadcrumbs sur ListUsers, ListRoles, ListCountries, ListPermissions (test list_pages_have_no_breadcrumbs). Pages de création non modifiées (absentes du diff).
3 OK. Deux traits partagés ; les getBreadcrumb() par page sont supprimés.
4 OK. Enum NavigationGroup::Settings utilisé par SettingResource et AbstractLocationResource ; lang location navigation.* supprimés, aucune référence restante. Test countries_are_in_the_settings_group.
5 OK. Clé retirée des lang fr/en, test API : home.greeting absent. HomeScreen sans salutation. (Aucune référence restante à home-greeting.)
6 OK dans le code et les tests. AppTopBar branché comme `header` des Tabs, logo sinon nom (AppTopBar.test.tsx, 2 cas). Marges haut des écrans retirées pour éviter un doublon de safe area.
7 OK. Trait ServesMediaOnRequestHost partagé par UserResource et ApplicationResource ; l'ancienne méthode privée est supprimée. Test ApplicationTest::test_the_logo_url_uses_the_host_the_app_called.
8 OK. `Translate = (key: string, params?: Record<string, string|number>)`. ApiMessageKey, MessageKey et OfflineKey supprimés, aucune référence restante. Tests de locale.ts : clé absente affichée telle quelle, nombres acceptés, zéro géré.
9 OK (voir base du verdict).

Ne couvre pas :
- Le rendu visuel : fil d'Ariane dans Filament, barre du haut sur appareil ou simulateur, insets de safe area, affichage réel du logo (expo-image est simulé dans les tests, les tests Livewire lisent getBreadcrumbs() et non le HTML).
- Les modifications humaines hors périmètre (favicon, public/images, TranslatableTabs).
- La navigation Paramètres rendue dans le menu (testée via getNavigationGroup() seulement).

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Texte périmé : app/Http/Controllers/Api/MessageController.php:28 (docblock repris dans openapi.json:364) et app/Support/MobileMessages.php:14 citent `home.greeting` en exemple alors que la clé n'est plus servie. Preuve : grep « home.greeting » dans app/. Non bloquant ; choisir un autre exemple et régénérer openapi.json.
- Commentaire de src/app/(tabs)/_layout.tsx : la ligne ajoutée dépasse la largeur des autres lignes du bloc (mise en forme du JSDoc seulement).
