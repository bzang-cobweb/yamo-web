RAPPORT QA — 2026-11-19-calendrier-offre (vérification finale)
Go : oui
Base du verdict : rejoué moi-même.
- web : `ddev artisan test` -> « Tests: 568 passed (3034 assertions) ». `route:list` : les 4 routes calendar / blocked-dates existent.
- mobile : `npx tsc --noEmit` sans erreur ; `npx eslint src` sans sortie d'erreur ; `npx jest --forceExit` -> « Test Suites: 51 passed, 51 total » et « Tests: 276 passed, 276 total ».
- OpenAPI : j'ai généré le document par `scramble:export` vers un chemin hors dépôt (copié puis supprimé), puis comparé nœud par nœud à `openapi.json`. Les chemins du calendrier (`/v1/offers/{offer}/calendar`, `/calendar/{date}`, `/blocked-dates/{date}` PUT et DELETE) et les `link` des actions de carte sont identiques. Seules 4 différences, toutes hors chantier : `birth_date` et `required` de RegisterRequest et UpdatePersonalInfoRequest (module Auth, non touché par 23e221e..7c6b6b9).
- Dépendances : `git diff 23e221e^..HEAD` ne touche ni composer.json, ni composer.lock, ni package.json ; le commit mobile 2454709 ne touche ni package.json ni package-lock.json.
- Code lu : BookingService, OfferAvailability, OfferCalendar, OfferCalendarController, routes, OfferListing, ReceivedBookingListing, OfferBookingForm, Field::calendar ; côté app OfferCalendarScreen, CalendarDateField, useItemActions, MonthGrid (balayage présent), route `offers/[id]/calendar` et son Stack.Screen.
- Tests ciblés lus : BookingAvailabilityTest (refus à la demande, refus à l'acceptation, notification des refusés auto, grisage dans le formulaire), OfferCalendarApiTest (404 non propriétaire sur mois, jour, PUT ; états des jours ; visites en point ; panneau ; bloquer et débloquer).
Critères : 1 à 9 satisfaits sur la base ci-dessus (voir réserves).
Ne couvre pas :
- l'interface réelle (simulateur iOS, rendu visuel : hachures, contour rouge, glissement, Modal natif, Alert de confirmation) : non vérifiée, seulement les tests jest et la lecture du code ;
- le critère « simulateur vu (iOS) » du contrat reste donc à la charge de l'auteur ;
- la concurrence réelle (le verrou `lockForUpdate` n'a pas été exercé en parallèle) ;
- les textes de.php / en.php relus seulement via les tests verts, pas relus un à un ;
- l'e-mail et le SMS des refus automatiques : seule la notification `BookingRequestRefused` est assertée.
- le champ `calendar` du formulaire n'apparaît pas dans openapi.json : les champs de formulaire (même `date_range`, `max_length`) n'y sont pas décrits ; cohérent, pas un écart.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Modules/Offer/app/Http/Controllers/Api/OfferCalendarController.php:51-71 (`block`) : le verrou de l'offre pris par `BookingService::lock` n'est pas pris ici. Un blocage simultané à une acceptation sur le même jour pourrait laisser un séjour accepté sur un jour bloqué (fenêtre étroite, non reproduite). À verrouiller dans une transaction si la garantie « jamais » doit tenir en concurrence.
- Modules/Offer/app/Services/BookingService.php:116-119 : le verrou est applicatif seulement (aucune contrainte en base contre deux séjours acceptés qui se chevauchent) ; non vérifié sur le moteur réel de production.
