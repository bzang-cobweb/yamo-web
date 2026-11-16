RAPPORT QA — étape 1 (web), tâche 03
Go : oui
Base du verdict : rejoué moi-même. `ddev artisan test` : 99 passés (359 assertions) ; RouteGuardTest 2 passés, tests/ non modifié (aucune nouvelle exception) ; `pint --test` : passed ; `ddev composer openapi` : openapi.json identique octet pour octet à l'export frais (restauré ensuite) ; grep de `auth.profile.locale`, `ProfileLocaleForm`, `UpdateMeRequest` dans Modules, app, routes, tests, openapi.json, .ia/context/project.md, config, lang : seule occurrence = test qui vérifie le 404 (+ décision). Essai réel sur yamo.ddev.site avec compte qa03@example.test : 3 formulaires servis avec titre (fr) ; locale -> 404 ; PATCH personal-info vide -> 422 traduit, e-mail/pseudo ignorés (200, e-mail inchangé) ; password : ancien faux, <8, non confirmé, identique -> 422 traduits ; succès 204 ; appareil A toujours connecté, appareil B -> 401 ; ancien mot de passe refusé au login, nouveau accepté ; preferences locale invalide -> 422, "en" -> 200 ; PATCH /auth/me -> 405 (GET seul). Structure : trois contrôleurs/services/FormRequest (FormBackedRequest, règles issues du formulaire). Messages : 24 clés fr et en, onglets, liens du profil, confirmations présents ; fichiers de langue fr/en de l'Auth avec clés identiques. Compte jetable supprimé, 0 compte @example.test restant.
Ne couvre pas : l'app mobile ; le rendu des textes en anglais clé par clé (parité de clés vérifiée, pas relecture de traduction) ; la couverture de chaque test unitaire ; les messages de validation en anglais (le compte test était en fr).

Bloquants : 0
Majeurs : 0
Mineurs : 0
