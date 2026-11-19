RAPPORT QA — nuit-2026-11-19 (vérification finale)
Go : oui
Base du verdict : rejoué moi-même : `ddev exec php artisan test` = 500 passed (2412 assertions) ; jest = 43 suites, 233 tests passed ; `tsc --noEmit` exit 0 ; `npm run lint` exit 0 ; composer.json/lock et package.json/lock sans diff (git status) ; `route:list` montre api/v1/offers/{offer}/reviews. Lecture du diff web et mobile contre les 7 critères.
Ne couvre pas : rendu visuel réel (appareil/navigateur), accessibilité testée au lecteur d'écran, défilement infini réel, volume (throttle 60/min non éprouvé), clés `error.unknown` / `offline.message` (utilisées par des écrans existants, présence serveur non vérifiée ligne à ligne), contenu de openapi.json hors grep (non régénéré, interdit).

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Le contrôleur reviews trie par updated_at puis id (cohérent avec la fiche) ; la décision dit « plus récent » : OK, mais un avis modifié remonte en tête — comportement hérité, à confirmer par l'humain.
- Les nouvelles flèches (24x24 + hitSlop 4) et le bouton Retirer sont sous la cible tactile de 44 pt recommandée ; libellés d'accessibilité présents (uploadMoveLeft/Right, « Voir/Éditer <titre> »).
- Le nouveau type de champ `label_hidden` n'apparaît pas dans openapi.json : le schéma des champs n'y est pas décrit (0 occurrence de `placeholder`), donc pas d'incohérence.

Constats par critère : 1 OK (404 brouillon même propriétaire testé ; Gate show => autre pays 404 ; 20/page, cursorPaginate, OfferReviewResource partagé avec la fiche, test d'égalité de forme). 2 OK (route déclarée dans _layout hors Stack.Protected ; lien affiché si count > latest ; useInfiniteQuery + List). 3 OK (pas de flèche aux extrémités, testé ; uploadMakeMain absent partout, grep vide). 4 OK (label_hidden servi, FieldShell masque, label conservé). 5 OK (stacked si image ou voir+éditer ; sans image ni link+edit inchangé). 6 OK (link /offers/{id} + edit + delete ; Voir/Éditer/Retirer ; carte ouvre encore le formulaire via edit prioritaire). 7 OK.
Régressions : `link` avec `edit` n'était pas affiché avant ; seul « Mes logements » sert les deux (favoris : link sans edit, adresses : ni l'un ni l'autre) → inchangés. Textes fr et en présents pour allReviews, view, edit, uploadMoveLeft/Right.
