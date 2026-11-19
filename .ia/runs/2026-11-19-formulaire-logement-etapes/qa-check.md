RAPPORT QA — 2026-11-19-formulaire-logement-etapes
Go : oui
Base du verdict : rejeu moi-même.
- Serveur : `ddev exec php artisan test Modules/Offer Modules/Form tests/Feature` = 192 passed (762 assertions), 0 échec (sortie : storage/app/qa-server.txt). Par dossier : Modules/Offer 63, Modules/Form 80, tests/Feature 49, tous verts.
- App : jest 37 suites / 203 tests passés ; `npx tsc --noEmit` sans erreur ; `npm run lint` 0 erreur, 1 warning (router.d.ts, fichier généré, hors chantier).
- Lecture du diff (web + mobile) contre les critères 1 à 5 :
  - C1 : `RentalDomain::formSteps()` déclare les 4 étapes avec les champs du contrat. Le test OfferApiTest vérifie titres et champs, et le POST steps/1 (refus, erreurs limitées à l'étape) et steps/4 (coordonnées refusées). DynamicForm affiche les champs dans l'ordre de l'étape (test jest dédié).
  - C2 : `OfferForm::steps()` renvoie [] sans étapes (test TranslationTest). Le champ non cité va à la dernière étape et l'étape vide est filtrée (lu dans le code, correct).
  - C3 : FieldShell ajoute « En :unit » après l'aide, NumberField n'affiche plus de suffixe à droite. L'unité du loyer suit « meublé » via `suffixWhen`.
  - C4 : `Field::money()` + `inCurrency()` ; fiche « 15 000 FCFA / mois » testée ; formulaire « CHF / mois » testé avec un pays CHF.
  - C5 : `Currency::symbol()` est utilisée par le formulaire (prix et charges), la fiche et la colonne prix du back-office (`Number::currency`). La grep de `currency` dans app, Modules et routes ne montre aucune autre concaténation de code brut.
  - `attributes()` : le diff n'ajoute aucun accès base (seulement `money()`). Inscription : RegisterForm non modifié, tests Form/Auth et FormSteps jest verts, pas de régression constatée.

Ne couvre pas : exécution sur simulateur ou appareil (affichage réel) ; test serveur qui joue le cas « champ non cité » et « étape sans champ » (code lu seulement) ; modification (`?record=`) non rejouée en navigateur ni sur simulateur ; `ddev composer openapi` (interdit) : cohérence du schéma OpenAPI non vérifiée ; rendu ICU de XAF en `en` non rejoué hors des tests.

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Back-office, liste des pays (CountryTable.php:37) : la colonne « Devise » affiche le code ISO brut (XAF). C'est la donnée de configuration du pays et pas un prix. Je la juge tolérable, mais une lecture stricte de la règle (« colonnes ») voudrait le symbole, ou le code avec son symbole. Le champ de saisie du code ISO (CountryForm) et son aide (« par exemple XAF ou CHF ») sont de la configuration : acceptable.
- Commentaires périmés qui citent « XAF / jour » : mobile DynamicForm.tsx:84 et Modules/Form/app/Support/Field.php:54. Ce sont des exemples de doc, pas un affichage.
- Pas de test serveur pour les règles « champ non placé rejoint la dernière étape » et « étape sans champ présent disparaît » (critère 2, seul « sans étapes » est testé).
