RAPPORT QA — 2026-11-19-regles-informations
Go : oui
Base du verdict : rejoué moi-même : `ddev artisan test --compact` = 508 passed (2503 assertions) ; filtre Offer = 110 passed ; mobile `npx jest --forceExit` = 44 suites / 236 tests verts ; `npx tsc --noEmit` = 0 erreur ; `npm run lint` = 0 erreur, 1 warning (router.d.ts, hors livrable). Lecture du code de 859197d et a30866b : étape Règles en 4e, heures 06:00-23:00 par demi-heure visibles si furnished=true, min_age 16-99, documents multiselect, pets/smoking/parties oui/non, other_information translatable 2000 car., rules/information servis à part et retirés de facts, onglet Règles distinct de Caractéristiques, section Accordion « Règles du logement » après Caractéristiques absente si rien, champs optionnels côté app (offre ancienne inchangée). openapi.json contient rules et information. Aucune dépendance ajoutée.
Ne couvre pas : rendu visuel appareil/navigateur, contenu intégral du diff openapi.json (hors énumérations pays), base locale, données réelles.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- RentalDomain.php : le docblock des AMENITIES (« Équipements proposés… ») est maintenant au-dessus de DOCUMENTS/INFORMATION_MAX_LENGTH ; AMENITIES a perdu son commentaire.
- Le déplacement de la spec vers specs/done/ est indexé mais non commité (git status : `RM specs/2026-11-19-regles-informations.md -> specs/done/...`), hors des deux commits.
