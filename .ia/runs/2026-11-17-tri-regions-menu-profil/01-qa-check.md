RAPPORT QA — 2026-11-17-tri-regions-menu-profil (vérification finale)
Go : oui
Base du verdict : rejoué moi-même
- web : `ddev artisan test --compact` = 258 tests, 1255 assertions, tous verts ; `ddev exec vendor/bin/pint --test` = PASS, 301 fichiers ; `migrate:status` : aucune migration en attente (2026_11_22_100000_create_profile_menu_links_table = Ran) ; route `api/v1/auth/me/menu` présente (route:list) ; openapi.json contient /v1/auth/me/menu (GET, 200 + 403, clés enum des 5 écrans), non régénéré par moi.
- mobile : `npx jest` = 28 suites, 106 tests verts ; `npx tsc --noEmit` code 0 ; `npx expo lint` code 0.
- Aucune modification de composer.json / composer.lock / package.json / package-lock.json (git status vide sur ces fichiers).
- Code relu contre les critères : 1 (colonne position + index, reorderable('position'), RegionPolicy::reorder = UPDATE_REGIONS hors super admin, test de refus sans permission) ; 2 (Country::regions() orderBy position,id ; PersonalInfoForm orderBy position,id ; creating() = max+1 par pays, testé ; migration tri Collator 'fr' sur le nom fr) ; 3 (table profile_menu_links, 5 clés, ressource sous NavigationGroup::Settings, policy sur UPDATE_SETTINGS, create/delete/deleteAny = false, pas de page create) ; 4 (contrôleur -> ProfileMenuService, actifs + ordonnés + filtre canal AuthChannels + libellé ajouter/changer ; tests 401, ordre, canal, langue) ; 5 (MENU_ROUTES, clé inconnue ignorée via Object.hasOwn, `menu.data ?? []` donc aucun lien sans menu, SignOutButton et DeleteAccountLink hors de la liste).
Ne couvre pas : rendu réel du glisser-déposer dans un navigateur (seul l'appel Livewire `reorderTable` est testé) ; exécution de l'app sur appareil/simulateur ; migration rejouée sur une base contenant des régions réelles (ordre alphabétique fr jugé par lecture du code, pas rejoué) ; openapi.json comparé à une régénération (interdite) ; revue de l'aspect visuel ; chaîne complète back-office -> app de bout en bout.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- ProfileMenuLinkPolicy utilise HasBeforeAbility : un super admin obtient true sur create/delete/deleteAny (le `before` passe avant les méthodes qui renvoient false). Aucune exposition constatée aujourd'hui (pas de page create, pas d'action de ligne dans la table, ni DeleteAction), donc le critère « sans création ni suppression » tient dans l'interface ; mais `ProfileMenuLinkResource::canCreate()/canDelete()` renvoient true pour un super admin et le test « neither created nor deleted » n'est joué qu'avec un compte à permission simple. Une action ajoutée plus tard serait autorisée au super admin. Suggestion : test avec super admin, ou policy sans le trait pour create/delete.
- Le ToggleColumn `active` n'est désactivé que côté UI pour un compte sans UPDATE_SETTINGS ; l'accès à la page étant déjà fermé par viewAny (même permission), pas d'exploitation possible (test 403 vert). Simple remarque de défense en profondeur.
