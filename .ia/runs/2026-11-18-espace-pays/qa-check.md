RAPPORT QA — 2026-11-18-espace-pays (vérification finale)
Go : non (un écart majeur à corriger avant livraison ; aucun bloquant)

Base du verdict : rejoué moi-même
- web : `ddev exec php artisan test` 375 passés (1862 assertions), 0 échec ; Pint --test PASS (394 fichiers) ; `scramble:export --fail-on-unknown` identique à openapi.json (cmp).
- mobile : jest 35 suites / 179 tests passés ; `tsc --noEmit` propre ; `npm run lint` code 0.
- Sondes jetables (sqlite mémoire, supprimées) sur l'API : X-Country absent/invalide/inactif/minuscule avec 2 pays actifs -> 422 sur listes, formulaires, POST/PATCH d'offres et d'adresses ; /auth/me, formulaire de connexion, /countries -> 200. Un seul pays actif : en-tête absent, invalide ou d'un pays inactif -> ce pays. Aucun pays actif : 422 sur le dépendant, 200 sur /me, /countries -> data vide. PATCH/DELETE d'une offre hors pays -> 404 ; PATCH d'une adresse hors pays -> 404 ; adresse d'un autre pays dans une offre ou dans le POST d'adresse -> 422. Offre sans pays absente de l'espace. Offre XAF affichée « 1 850 FCFA ». Liste « Mes logements » : 7 requêtes, pas de N+1.
- Back-office (Livewire, sondes) : devise d'un pays modifiable (« eur » -> EUR, « xof2 » refusé) ; offres filtrables par pays ; création avec pays -> devise du pays (XAF).
- Mobile (sonde jetable lancée hors du dépôt, jest --roots) : voir majeur ci-dessous.
- Code relu : CurrentCountry::require() n'est appelé dans les policies que hors `Filament::isServing()` (aucun effet sur le back-office) ; migrations up/down présentes et testées (offres -> pays de l'adresse, sinon seul pays actif, sinon vide ; devises ICU).

Ne couvre pas : exécution réelle de l'app sur appareil/simulateur (splash, offline, parcours visuel de `_layout.tsx` relus et non rejoués) ; migration sur la base DDEV locale (interdit) ; les 245 pays actifs en local (effet UX : l'écran d'entrée y listerait 245 pays, à signaler, pas un écart) ; seeder CountrySeeder rejoué ; requêtes de l'app réellement émises avant le choix du pays (analyse de code seulement : seuls /countries, /locales et la restauration de session partent sans en-tête).

Bloquants : 0

Majeurs : 1
- Écran d'entrée : changer de langue après avoir touché un pays fait perdre la sélection du pays, et tout l'écran disparaît le temps du rechargement. Cause : `CountryProvider` clé la requête des pays sur la langue (`queryKey: ['countries', locale]`, sans placeholderData). Un changement de langue (`chooseLocale`) vide `countries`, `ready` repasse à false, `RootNavigator` démonte `EntryScreen` (`isLoading`), puis le remonte et son `useState selected` repart à null. Reproduit par une sonde jest : avec 2 pays, choisir SN puis la langue « en » -> `entry-country-SN` n'est plus sélectionné. Touche le critère 2 (premier lancement à plusieurs pays, ordre langue après pays) ; sans effet avec un seul pays (choisi d'office). Pistes : `placeholderData: keepPreviousData`, ou remonter `selected` dans le provider. Aucun test existant ne change de langue (chooseLocale est simulé).

Mineurs : 3
- Même mécanisme après connexion : si la langue du compte diffère de celle de l'appareil, `locale` change, les pays se rechargent et tout le navigateur (Stack) est démonté puis remonté (état de navigation perdu) ; à vérifier sur appareil.
- Back-office : une offre peut recevoir un pays différent de celui de son adresse (sonde : adresse SN, pays CM acceptés) ; la devise suit le pays de l'offre. Hors critères, mais incohérent.
- Aucun test automatisé pour le critère 6 (devise d'un pays modifiable, filtre des offres par pays dans le back-office) : vérifié seulement par mes sondes jetables ; à ajouter. Le setCountry de l'ApiClient est aussi appelé pendant le rendu de CountryProvider (effet de bord au rendu, fonctionnel mais fragile).

## Revérification (après correctifs)

Go : oui. 0 bloquant, 0 majeur.

Rejoué moi-même :
- Mobile (771f9a3) : ma sonde du changement de langue (deux pays, SN puis « en ») passe, SN reste choisi ; jest 35 suites / 180 tests ; tsc propre ; lint code 0.
- Web (8c160c6) : 376 tests passés (1867 assertions) ; Pint PASS (394 fichiers) ; OpenAPI exporté identique à openapi.json.
- Back-office, sonde jetable : adresse SN avec pays CM refusée (erreur sur address_id, aucune offre créée) ; adresse CM acceptée, devise XAF.
- Dépôts sans fichier modifié ; fichiers temporaires supprimés.

Restent, non bloquants : démontage de la navigation après connexion si la langue du compte diffère (mineur 1, non rejoué, probablement réglé par le même placeholderData mais non vérifié) ; test automatisé du critère 6 non ajouté (non vérifié dans ce commit). Ne couvre toujours pas : exécution sur appareil, base DDEV locale, 245 pays en local.
