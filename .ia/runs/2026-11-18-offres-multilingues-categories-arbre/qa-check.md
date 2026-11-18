RAPPORT QA — 2026-11-18-offres-multilingues-categories-arbre
Go : oui
Base du verdict : rejeu personnel.
- `ddev exec php artisan test` : 358 passés (1781 assertions), 0 échec, suites Modules découvertes (dont TranslationTest et CategoryTreeTest).
- Pint --test : PASS, 384 fichiers.
- scramble:export --fail-on-unknown : sortie identique à openapi.json (diff vide).
- mobile `npx jest` : 33 suites, 171 tests passés ; aucun fichier mobile modifié.
- MariaDB 11.8 locale, en transaction annulée (compte @example.test, 0 reste) : offre créée en `en` seulement lue en fr/en/de = texte `en` ; description `de` seule rendue en toute langue (repli compte -> défaut -> n'importe laquelle OK). Recherche `title->fr/en` : « ÉTUDIANT », « étudiant », « cosy », « appart » trouvent ; « fr » et « "fr" » ne correspondent pas aux clés de langue (0). Catégories : chemin « A › B », nom lu en en, boucle (parent = descendant et soi-même) refusée avec message, suppression d'un parent refusée avec message au niveau modèle.
- Relu le diff des 3 commits : conservation des autres langues (OfferService::translations), migration aller/retour (testée up/down/up, repli sur le premier texte), Field::translatable limité aux textes (LogicException), onglets par langue en back-office, tri racines/sœurs (reorderable position, test reorderTable), options() en ordre d'arbre avec chemins (app et back-office), seeder par slug sans déplacement (testé), eager loading category.parent.parent dans OfferTable.
Ne couvre pas : migration aller/retour sur MariaDB (rollback de la base locale interdit ; rejouée uniquement sur SQLite par les tests) ; rendu visuel navigateur du glisser-déposer et des onglets ; fichiers de l'autre session (project:init : Domain::modules(), app/Support/EnvFile.php, non commités, ignorés) ; performances sur gros arbres.

Bloquants : 0

Majeurs : 0

Mineurs : 4
- Suppression groupée d'un parent refusée par un hook `before` + `cancel()` : aucun test ne la couvre (seuls l'action unitaire et le modèle sont testés). Filet : le modèle lève une LogicException.
- Effet global de `Translatable::fallback(default fr, fallbackAny)` dans AppServiceProvider : le repli passe de `app.fallback_locale` (en) à fr puis n'importe quelle langue, pour pays, régions, catégories aussi. Aucune régression constatée (suite verte) ; changement de comportement assumé mais non testé sur ces modèles.
- N+1 résiduel : `category.parent.parent` couvre 3 niveaux ; au-delà, Category::path() charge le parent par requête (un arbre plus profond que le niveau 3 dans la table des offres). OfferCategory::options() et le hook saving (descendantIds à chaque sauvegarde avec parent) font des requêtes supplémentaires, négligeables.
- La recherche reste sensible aux accents (« etudiant » ne trouve pas « étudiant »), comportement de ContainsSearch antérieur au chantier ; détail legacy `(array)` d'un attribut devenu traduisible garderait une clé 0 pour une ancienne valeur texte (aucune donnée concernée aujourd'hui).
