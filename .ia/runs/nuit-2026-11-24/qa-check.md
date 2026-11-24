RAPPORT QA — 2026-11-24-partenaires-validation (nuit)
Go : non (strictement, sur le critère 5 : pint) ; fonctionnellement les critères 1 à 4 sont conformes

Base du verdict (rejoué) :
- web : `ddev artisan test` = 691 passed (4283 assertions)
- app : `npx jest --watchAll=false` = 61 suites, 369 tests passed ; `npx tsc --noEmit` = exit 0
- `ddev exec vendor/bin/pint --test` = ÉCHEC (exit 1) : single_quote sur Modules/Partner/lang/{de,en,fr}/mobile.php
- Lecture du diff main...HEAD (web et mobile) contre les critères 1 à 4.

Ne couvre pas : exécution réelle sur appareil/simulateur, rendu Filament dans un navigateur, envoi réel e-mail/SMS, file d'attente en production, relecture des traductions de/en.

Bloquants : 1
- Critère 5 : pint --test échoue sur Modules/Partner/lang/{de,en,fr}/mobile.php (règle single_quote). Ces fichiers ne sont pas modifiés par la branche (absents du diff main...HEAD, dernier commit 64d919b) : défaut préexistant sur main, correctif mécanique (`pint` sur ces 3 fichiers). Bloquant à la lettre du critère ; à lever ou à accepter explicitement.

Majeurs : 0

Mineurs : 1
- mobile/src/features/auth/screens/ProfileListScreen.tsx (autour de l'ancienne fonction openFooter supprimée) : trois lignes vides consécutives restent avant `export { SEARCH_DELAY }`.

Constats par critère :
1. OK. PartnerService.php propose() et change() dispatchent PartnerSubmitted ; PartnerNotifierService::submitted notifie tous les comptes (auteur compris) avec texte own/others ; statusChanged() ignore PENDING (pas de doublon). MyPartnerController passe $user à change(). Tests PartnerStatusNotificationTest et PartnerApiTest passent.
2. OK. PartnerResource::validateAction visible si PENDING et can('update'), via PartnerService::accept (statut PUBLISHED, déclenche la notification de statut « accepté »). Présente en liste (PartnerTable) et sur EditPartner. Tests PartnerResourceTest passent.
3. OK. MyPartnerListing et PartnerListing::footer (« Devenir partenaire » : formulaire si connecté, link /sign-in si visiteur) ; mobile : openListFooter gère /sign-in (route src/app/sign-in.tsx existe), PartnersScreen affiche FixedButtonBar. Testé côté API et jest.
4. OK. PartnerNotificationFilter enregistré dans PartnerServiceProvider : type « partner » restreint aux deux classes de notification, filtre partner_status (visible si type=partner, valeur validée par l'enum), pastille via tag(). PartnerNotificationFilterTest passe.
