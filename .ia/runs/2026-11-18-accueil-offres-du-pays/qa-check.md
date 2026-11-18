RAPPORT QA — 2026-11-18-accueil-offres-du-pays (offer.browse, accueil mobile, DemoSeeder)
Go : oui
Base du verdict (rejoué moi-même) :
- web : `ddev exec php artisan test` = 384 verts (1903 assertions), 0 échec ; Pint --test propre ; `scramble:export --fail-on-unknown` identique à openapi.json (diff vide).
- mobile : jest 34 suites / 183 tests verts ; `tsc --noEmit` exit 0 ; `npm run lint` exit 0. Aucune référence restante à WelcomeScreen/HomeScreen/CountryFlag (src, README).
- Test jetable hors arbre (supprimé) sur offer.browse, 26 offres publiées CM : 2 requêtes SQL (pas de N+1, `with('address')`), page 1 = 20 items + curseur, page 2 = 6 ; recherche d'un titre présent seulement en `en` depuis un contexte `fr` = 1 résultat ; réponse sans `user_id` ; items `edit`/`delete` = null ; listes enregistrées autres que offer.browse (auth.addresses, offer.mine) = 401 pour un visiteur (défaut `user !== null` conservé, seule OfferBrowseListing surcharge canBeViewedBy).
- Base locale (lecture) : demo-membre@example.test a 20 offres : 16 published, 2 draft, 2 archived.
- Lecture du code : requête filtrée sur country_id courant + status published + ordre id desc ; DemoSeeder lève LogicException en production, rejouable (retour si >= 20 offres), compte et mot de passe documentés en en-tête ; AppTopBar (logo, LocaleSwitcher, CountrySwitcher) utilisée par (tabs)/_layout et GuestHomeScreen ; CountrySwitcher désactivé si un seul pays, `enter` invalide les requêtes (hors countries) au changement ; chooseAppLocale : invité = stockage appareil, membre = PATCH me/preferences puis setUser.
Ne couvre pas : rendu visuel réel sur appareil/simulateur (barre fixe, modale, bas de l'écran visiteur), parcours bout en bout contre l'API réelle, 404 sans domaine/422 sans pays rejoués seulement via les tests existants, performance sur gros volumes, `db:seed` réel non rejoué (interdit), changement de pays d'un membre non persisté côté compte (hors contrat).

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Mobile `AuthProvider.chooseAppLocale` : une erreur du PATCH (réseau, 4xx) est avalée sans message (`() => undefined`) ; la langue ne change simplement pas.
- Tests web de offer.browse ne couvrent pas la pagination, ni l'absence de `user_id`, ni le nombre de requêtes (vérifiés ici par test jetable, non conservé).
- Accueil visiteur : la barre est rendue dans GuestHomeScreen alors que le membre la reçoit via le header des onglets ; cohérent mais à deux endroits (risque de dérive).
