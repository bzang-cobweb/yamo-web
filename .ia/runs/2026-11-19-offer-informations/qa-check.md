RAPPORT QA — 2026-11-19-offer-informations
Go : oui
Base du verdict : rejoué moi-même — `ddev artisan test --compact` : 512 passed (2539 assertions) ; mobile `npx jest --forceExit` : 45 suites, 241 tests passed ; `npx tsc --noEmit` : aucune erreur ; `npm run lint` : aucune erreur. Lecture du code des commits web 74f6c8b et mobile 7e87818 contre chaque critère (migration, modèle, champ repeater, OfferForm, OfferService, OfferDetailResource, config offer.sections, Filament, RepeaterField, OfferDetailScreen, openapi.json). Aucun `other_information` restant (grep). Aucun composer.json/package.json modifié.
Ne couvre pas : exécution de la migration sur des données réelles (interdit : migrate) ; rendu visuel app/back-office ; régénération OpenAPI (cohérence lue, non rejouée) ; autres commits.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- La migration (up : recopie vers un bloc + retrait de details ; down) n'est couverte par aucun test ; vérifiée à la lecture seulement. Cas limite : un texte présent dans une seule langue autre que fr/en reçoit le titre « Autres informations » (fr) par repli, acceptable.
- down() ne restaure que le premier bloc de chaque offre (sa description, sans titre) ; perte assumée à un retour arrière, à connaître.
