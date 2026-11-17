RAPPORT QA — 2026-11-17-tri-regions-menu-profil, correction (régions alphabétiques, menu en code)
Go : oui
Base du verdict : rejoué moi-même — web `ddev artisan test --compact` : 253 passés (1245 assertions) ; `ddev exec vendor/bin/pint --test` : PASS 295 fichiers ; `ddev artisan migrate:status` : aucune en attente, 2026_11_22_110000_drop_profile_menu_links_table « Ran » ; mobile `npx jest` : 28 suites / 106 tests verts ; `npx tsc --noEmit` et `npx expo lint` : sans sortie d'erreur. Lu : diff web (HEAD) et mobile, ProfileMenuService (const MENU ordonnée, filtre isAvailable, libellé labelFor), migration drop (up = dropIfExists, down recrée), PersonalInfoForm::regionOptions (Collator sur App::getLocale, plus de orderBy position), Country::regions() toujours orderBy position puis id, Region::creating place la région en fin de pays, openapi.json (/v1/auth/me/menu présent, enum des région réordonné). grep ProfileMenuLink / profile_menu hors vendor : seulement la migration de création (inchangée), la nouvelle migration drop, la spec archivée et .phpunit.result.cache (artefact). Aucune modif composer.json/lock ni package.json. Mobile : seuls 3 commentaires changés.
Ne couvre pas : exécution réelle de l'app mobile ni du glisser-déposer dans le navigateur (tests Filament Location non modifiés, passés dans la suite) ; test de la migration down ; ordre alphabétique avec d'autres locales que fr/en ; la table réelle en base dev (le statut « Ran » indique seulement que la migration est enregistrée).

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Commentaire obsolète : Modules/Auth/app/Http/Controllers/Api/ProfileMenuController.php:10 dit « réglé dans le back-office », contraire à la décision (menu défini dans ProfileMenuService).
- Collator exige l'extension PHP intl, absente des `require` de composer.json (présente dans DDEV) ; aucune autre utilisation de Collator dans le code. À déclarer (ext-intl) ou à noter ; hors critère « aucune dépendance ajoutée » si l'on s'en tient aux paquets.
