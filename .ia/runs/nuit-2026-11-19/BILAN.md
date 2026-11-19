# Nuit du 2026-11-19 — YaMo
Branche : nuit/2026-11-19 dans les deux dépôts (web depuis main@071f7d8, mobile depuis main@d0d8d2e)
État : terminée avec réserves

## Ce qui attend votre attention
- **Contrat exécuté sans validation** (mode nuit) : `specs/done/2026-11-19-nuit-avis-photos-liste.md`, lectures retenues dans « Ce que j'ai décidé seul ».
- **Ordre des photos par flèches ‹ ›**, pas par glisser-déposer (aucune dépendance la nuit). Un glisser-déposer demanderait une dépendance à autoriser.
- **Tri des avis** par dernière modification : un avis modifié remonte en tête (comme sur la fiche). À confirmer.
- Flèches et « Retirer » plus petits que la cible tactile recommandée (44 pt).
- Intégrer la branche `nuit/2026-11-19` dans `main`, dans **les deux dépôts** (rien n'est poussé).

## Fait
1. **Tous les avis** — web `9e3f5c4`, mobile `d384dab` : `GET /api/v1/offers/{offer}/reviews` (20 par page), « Voir les N avis » sur la fiche, écran de tous les avis.
2. **Photos** — web `925bb7a`, mobile `f932989` : flèches pour l'ordre (vérifié au simulateur, ordre enregistré), libellé « Photos » masqué (`Field::hideLabel()`).
3. **« Retirer » sur sa ligne** + demande de la nuit **« Voir, Éditer, Retirer »** — web `e45c3e7`, mobile `fccc6fd` : photo pleine largeur, trois actions sous le contenu, « Voir » ouvre la fiche (brouillon compris).
- Preuves : 500 tests serveur, 233 tests app, tsc et lint propres, OpenAPI régénéré, QA indépendante GO (`qa-check.md`).

## Reste à faire
- Rien de la demande. Voir la QA : écran des avis non vu au simulateur (aucune offre démo avec plus de 5 avis).

## Décidé seul
- Voir les 4 décisions `.ia/context/decisions/2026-11-19-nuit-*.md` ; la décision du champ upload est marquée « remplacée en partie ».

## Défauts trouvés
- `.ia/context/project.md` cite `composer.lock:2420` pour Laravel ; la ligne est maintenant 2441 (version inchangée, v13.42.0). Noté, non corrigé.
- Avertissement OpenAPI « Cannot infer the resource model » sur `OfferReviewPageResource` (sans effet).
