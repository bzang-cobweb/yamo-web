# QA indépendante — 2026-11-19-reservations

1. Première passe : **NO-GO** — `mobile/src/features/form/__tests__/DateField.test.tsx` ne s'exécutait pas (type déclaré dans la fabrique `jest.mock`). Le reste conforme : suite web verte, routes et OpenAPI présents, bouton servi selon meublé / non meublé, back-office en lecture.
2. Correctif mobile bcea8a6.
3. Contre-vérification : **GO** — 47 suites / 255 tests verts, tsc propre, test des bornes (min, max, date d'ouverture) relu.
- Non couvert par la QA : simulateur, envois réels, parcours de bout en bout (aucune requête d'écriture), propriétaire en direct.
