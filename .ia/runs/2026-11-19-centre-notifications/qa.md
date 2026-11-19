# QA indépendante — 2026-11-19-centre-notifications

- Verdict : **GO** (0 bloquant, 0 majeur).
- Rejoué : suites web (554) et app (49 suites, 262 tests), tsc, routes, planification, export OpenAPI frais (chemins des notifications identiques), relecture du code (file, `afterCommit`, canaux, liens internes seulement, onglet et pastille, garde visiteur).
- Mineur : l'export frais diffère sur `birth_date` (obligatoire) : dû au réglage local « âge minimum = 21 » ; le fichier commité est généré réglage vidé, volontairement.
- Non couvert : simulateur, travailleur en marche, `.env` (non ouvert), textes en/de au-delà de leur présence.
