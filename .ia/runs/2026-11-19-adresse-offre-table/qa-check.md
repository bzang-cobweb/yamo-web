RAPPORT QA — 2026-11-19-adresse-offre-table
Go : oui
Base du verdict : rejeu moi-même
- `ddev artisan test --compact` : 501 passés, 2431 assertions, 0 échec (arbre avec 2 modifs non commitées étrangères : Offer::detailIn et OfferApiTest ; non évaluées).
- mobile : `npx jest --forceExit` 43 suites / 233 tests verts ; `npx tsc --noEmit` sans sortie (propre) ; `npm run lint` (expo lint) 0 erreur, 1 warning (router.d.ts, hors commit). `npx eslint src` direct non exécutable dans l'environnement (rtk), remplacé par expo lint.
- Lecture du code des commits 1bde6c7 / 69494f4 : migration (lecture des lignes avant retrait, drop address_description + address_id, création offer_addresses unique/cascade, recopie lien + indication, down() restaure colonnes et données) ; OfferService::syncAddress (clé absente = garde, vide = null, sans adresse = ligne supprimée) ; Offer::address en HasOneThrough ; cities() via offer_addresses ; OfferDetailResource clé `indication` ; formulaire Filament groupé sur offerAddress avec condition address_id ; OpenAPI mis à jour (indication).
- `migrate:status` : migration 2026_11_29_110000 « Ran » (lecture seule).
- grep address_description dans web (hors .ia) et mobile/src : seulement les anciennes migrations, la nouvelle migration (recopie) et le contrat.
Ne couvre pas : exécution de la migration sur des données réelles de la base locale (non rejouée, interdit) ; recopie de l'indication non assertée par un test (seul address_id l'est) ; rendu réel en navigateur/appareil ; régénération OpenAPI (vérifiée à la lecture du diff, pas rejouée) ; les modifs non commitées de l'arbre.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Le test de migration (CountrySpaceTest) vérifie la recopie de address_id mais pas celle de address_description vers indication, ni le chemin down() avec indication.
- La clé `address_id` reste dans Domain::COMMON_FIELDS (inoffensif, nom réservé encore utilisé par le formulaire Filament).
