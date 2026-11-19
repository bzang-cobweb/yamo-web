RAPPORT QA — 2026-11-19-filtres-vitrine
Go : oui
Base du verdict : rejeu serveur `ddev exec php artisan test Modules/Offer Modules/Form Modules/Auth tests/Feature` = 435 passés (2076 assertions) au second passage ; app : jest 39 suites / 214 tests verts, `tsc --noEmit` exit 0, `npm run lint` exit 0. API locale (X-Country CM, lecture seule) : ville Douala 10 ; Yaoundé 6 ; furnished true 8 / false 8 / any 16 ; bedrooms=2 7, =4 2 ; type studio 4, house 3 ; price_min=100000 6, price_max=100000 10 ; available_from 2026-11-19 3, 2000-01-01 0, 2030-01-01 16 ; invalides ignorés (HTTP 200, 16 items) : price_min=abc, -5, available_from=garbage, property_type=villa, filter=zzz (non tableau), filter[city][]=a, bedrooms=99999999999999999999, clé inconnue. `filters` servi : city, price_min, price_max, property_type, furnished (défaut any), bedrooms, available_from, en fr.
Lecture du code : clés JSON `details->…` en dur dans RentalDomain (aucun nom venant de la requête) ; valeurs liées en paramètres ; seuls les champs déclarés sont lus par validFilters. Liste sans filtres : `filters()` = [] par défaut, donc pas de lien (`definition.filters.length > 0`). Badge : price_min/max comptés pour un, « any » non compté, absent à zéro. Filtres tenus en mémoire (store de module). Épinglées filtrées dans pinnedItems.
Ne couvre pas : rendu visuel sur appareil/simulateur, parcours manuel de l'écran des filtres, cas d'un `available_from` JSON null (non présent en base locale ; MariaDB renverrait 'null' texte, non couvert par whereNull), anglais testé par lecture seule, openapi.json non régénéré par moi (interdit), performance de la requête des villes.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Premier passage de la suite serveur : 1 échec (UserAddressApiTest, `filters` absent de l'attendu) ; rejoué isolé puis en suite complète, tout passe (le fichier a été modifié entre-temps, git diff montre `'filters' => []` ajouté). Vérifier que ce changement est bien dans le commit.
- Un `available_from` stocké en JSON null (et non absent) ne serait pas traité comme « libre tout de suite » sur MariaDB ; à couvrir si le formulaire peut enregistrer null.
