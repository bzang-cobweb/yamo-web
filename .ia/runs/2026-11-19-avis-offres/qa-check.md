RAPPORT QA — 2026-11-19-avis-offres
Go : oui
Base du verdict : rejeu serveur `ddev exec php artisan test Modules/Offer Modules/Form Modules/Auth` = 311 passed (1693 assertions) ; mobile `npx jest` = 37 suites / 201 tests passed ; `npx tsc --noEmit` code 0 ; `npm run lint` code 0. Lecture du code : policy, routes, contrôleur, requête, formulaire, resource de la fiche, listes, ListItem, ressource Filament, migration, textes fr/en, composants mobile. openapi.json contient la route /v1/offers/{offer}/review, rating, highlight, can_review.
Ne couvre pas : vérification au simulateur/navigateur (aucun rendu réel), appareil physique, accessibilité réelle (VoiceOver/TalkBack), migration sur base locale, DemoSeeder non exécuté (lu seulement), régénération d'openapi.json (contenu vérifié, pas sa fraîcheur exacte), ni les autres modules (Form/Auth passés, le reste de la suite complète non lancé), performances sous volume.

Bloquants : 0

Majeurs : 0

Mineurs : 5
- Aucun test ne couvre le super administrateur sur la règle `review` (OfferPolicy::before l'exclut bien du contournement, vérifié à la lecture seulement).
- Aucun test n'affirme l'absence d'e-mail / nom de famille dans le bloc `reviews`. Lecture : seul `first_name` est servi, repli sur `name` (= prénom + nom) seulement si first_name est vide ; first_name est obligatoire à l'inscription, risque faible mais le repli pourrait fuir un nom de famille.
- OfferReviewPolicy utilise HasBeforeAbility : le super administrateur obtient true pour create/update. Sans effet aujourd'hui (la resource n'a qu'une page index), mais contredit « ni créer ni modifier » au niveau policy.
- « Dernier avis » = plus grand id (latestOfMany) : un avis modifié ne remonte pas comme dernier commentaire.
- Le retrait de son avis (DELETE) sur une offre devenue archivée répond 404 : le membre ne peut plus retirer son avis tant qu'elle n'est pas republiée.

Constats positifs : policy `review` 404 (non publiée / autre pays) puis 403 (propriétaire), routes sous auth:sanctum (401 visiteur) et garde can: ; formulaire et route partagent la même validation (FormBackedRequest, rating integer between 1,5, commentaire requis max 1000) ; unicité en base (offer_id,user_id) ; listes sans N+1 (withAvg/withCount/with latestReview, Filament with offer,user) ; rating/highlight nuls sans avis et pour offre non publiée dans offer.mine ; textes fr et en présents (review.php, mobile.php, permissions) ; permissions données au rôle admin.
