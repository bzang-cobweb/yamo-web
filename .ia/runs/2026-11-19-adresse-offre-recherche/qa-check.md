RAPPORT QA — 2026-11-19-adresse-offre-recherche (vérification finale)
Go : oui

Base du verdict (rejoué par moi) :
- Serveur : `ddev exec php artisan test Modules/Offer Modules/Location Modules/Form Modules/Auth tests/Feature` : 483 passés, 2306 assertions, 0 échec (sortie : web/storage/app/qa-suite.txt).
- App : jest 41 suites / 218 tests passés ; `tsc --noEmit` sans erreur ; lint 0 erreur, 1 avertissement (router.d.ts, fichier généré, hors chantier).
- OpenAPI : JSON valide, contient `/v1/addresses/suggestions` ; `scramble:export` vers un fichier temporaire du conteneur (supprimé) est identique octet pour octet à openapi.json (rien régénéré dans le dépôt).
- Lecture du diff contre chaque critère :
  1. OfferForm : street (suggestions `/v1/addresses/suggestions`), zip facultatif, city ; address_id retiré ; étape « Adresse et catégorie » = street, zip, city, category_id.
  2. Route : auth:sanctum + throttle:60,1 ; < 3 caractères = vide ; limite 10 ; pays courant ; réutilise `AddressService::suggest()` ; la ressource ne sert que value, label, fill{zip,city}. Échappement `%`/`_`/`!` vérifié par `ContainsSearch::pattern('a%_!b')` = `%a!%!_!!b%`.
  3. OfferService : `findOrCreate` par pays et empreinte ; carnet non touché (test : 0 entrée) ; required_with croisé rue/ville + required ; préremplissage à la modification ; sans street ni city envoyés, l'adresse actuelle est conservée ; rue ou ville vide : aucune adresse.
  4. App : suggestions dès 3 caractères après 300 ms, choix remplit le champ et `fill`, liste masquée après choix ; le service refuse `https://evil.test` et `/v1/../admin` (test). Carnet `auth.address` : `suggestions` = null (test mis à jour).
  5. Décisions : les trois décisions du 2026-11-18 portent la ligne « Remplacée en partie par : 2026-11-19-contrat-adresse-offre-recherche.md ».
- Régression back-office / carnet : les suites Modules/Auth, Modules/Offer et tests/Feature passent ; le back-office n'est pas modifié.

Ne couvre pas : simulateur / rendu réel et ergonomie de l'app ; base MariaDB locale (tests sur la base de test) ; charge réelle du throttle ; sécurité des données au-delà du risque assumé dans la décision ; tests e2e app+serveur ensemble.

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Aucun test ne couvre la limite de 10 résultats, le throttle (429) ni le contournement `X-Country` ; ces comportements sont corrects à la lecture du code mais non prouvés par un test.
- Un envoi avec `street` seul sans `city` retombe sur `required_with` (422) : correct ; mais un envoi avec une rue et une ville vides sur un domaine sans adresse obligatoire efface l'adresse (null), c'est voulu (documenté dans le code).
- `X-Country` choisit le pays des suggestions pour tout compte connecté (comportement général de l'espace pays, comme ailleurs) ; à garder en tête avec le risque assumé.
