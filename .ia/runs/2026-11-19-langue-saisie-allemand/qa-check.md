RAPPORT QA — 2026-11-19-langue-saisie-allemand (web 554a7d8, mobile c4959db)
Go : non (un bloquant : OpenAPI pas à jour)

Base du verdict (rejoué) :
- `ddev artisan test --compact` : 518 passés, 2648 assertions.
- mobile : `npx jest --forceExit` 46 suites / 246 tests verts ; `npx tsc --noEmit` sans erreur ; `npm run lint` sans erreur.
- Script jetable (hors dépôt) : tous les `lang/fr/*.php` du projet et des modules ont leur `lang/de` ; clés égales, sauf `Modules/Form/lang/de/contact.php` (listes de mots à contourner propres à l'allemand, écart légitime). `lang/de.json` : 230/230 clés de fr.json.
- tinker (lecture) avec fr/en/de : MobileMessages 105 clés dans les 3 langues, 0 manquante ; seulement 3 textes de, identiques au français.
- GET /api/v1/messages en `Accept-Language: de` : réponse en allemand.
- Lecture du code : offers.locale (migration, création API via OfferService, back-office CreateOffer), repli demandé > origine > défaut > autre (Offer, OfferInformation, detailIn, OriginalLocale), étape « Langue » à l'édition seulement et si plusieurs langues, `reloads` dans Field::toArray, validation d'étape avec `?record=`, titre BO exigé dans au moins une langue, catégories et pays en de (migrations), APP_LOCALES et textes hors ligne de, app : params de rechargement, confirmation Alert, record envoyé à validateStep. Les tests d'OfferApiTest couvrent ces comportements (création sans étape, étape 1, rechargement, PATCH d'une seule langue, langue inconnue 422, app mono-langue).
- tests/TestCase fr/en : ne masque pas de test devant voir de. Les tests qui portent sur de déclarent leur config (GermanTranslationsTest, OfferApiTest). La parité des messages de l'app (MessagesApiTest::test_every_language_has_exactly_the_same_keys) ne couvre plus de, mais je l'ai vérifiée à la main (voir plus haut) et GermanTranslationsTest couvre les fichiers lang.

Ne couvre pas : écriture/migrations sur la base locale (non jouées : les migrations 2026_11_29_* ne sont vérifiées que par lecture et OfferLocaleMigrationTest) ; appels authentifiés de l'API locale ; rendu sur appareil ; relecture complète de l'allemand (échantillon : offer.php, rental.php, validation.php, contact.php, textes hors ligne) ; contenu du back-office en navigateur.

Bloquants : 1
- OpenAPI pas à jour : le commit n'ajoute à openapi.json que `de` dans l'enum des langues. `POST /v1/forms/{form}/steps/{step}` n'a pas le paramètre de requête `record`, alors que `FormStepController::validate` le valide désormais (`request->validate(['record' => ...])`). Reproduire : python3 sur openapi.json, chemin `/v1/forms/{form}/steps/{step}`, parameters = form, step seulement. Il faut régénérer (`ddev composer openapi`, interdit à la QA).

Majeurs : 0

Mineurs : 2
- `MessagesApiTest::test_every_language_has_exactly_the_same_keys` s'exécute sur fr/en seulement depuis TestCase ; la parité des textes de l'app en allemand n'est plus vérifiée de façon permanente (seules 2 clés le sont, dans GermanTranslationsTest). Suggestion : y fixer `['fr','en','de']`.
- Le commentaire de `OfferForm::locale()` a un docblock orphelin en doublon (l'ancien « Langue de saisie : celle du compte » reste au-dessus du nouveau).
- Hors périmètre du commit : le déplacement `specs/…langue-saisie-allemand.md` -> `specs/done/` est dans l'index mais pas commité.


## Contre-vérification (après correction, commit 95f4569)

Go : oui

Base du verdict (rejoué) :
- `ddev artisan test --compact` : 518 passés, 2649 assertions (MessagesApiTest seul : 7 passés).
- `openapi.json` (lu avec python3) : `POST /v1/forms/{form}/steps/{step}` a les paramètres form (path), step (path), `record` (query, string, non requis, exemple 12) ; pas de `requestBody` (clés : operationId, summary, tags, parameters, responses, security). Cohérent avec `#[QueryParameter('record', ...)]` de `FormStepController`.
- Contrôleur : lit `$request->query('record')`, lie `forRecord($record)` si chaîne non vide d'au plus 64 caractères ; sinon formulaire sans enregistrement. Plus de `$request->validate` sur `record`.
- Valeur invalide ignorée sans erreur : acceptable, la validation d'étape ne porte que sur les valeurs de l'étape et `record` n'est qu'un contexte d'adresse ; le repli est le comportement de création, sans fuite ni effet d'écriture. L'écart avec un 422 est documenté dans le commentaire du code, mais pas dans l'OpenAPI (mineur).
- `MessagesApiTest::test_every_language_has_exactly_the_same_keys` fixe désormais `app.supported_locales` à fr, en, de : parité couverte pour les trois langues.
- Docblock orphelin de `OfferForm::locale()` supprimé (vu dans le diff).

Ne couvre pas : régénération de l'OpenAPI par `ddev composer openapi` (interdit ; cohérence jugée par lecture du fichier et de l'attribut, non par diff de génération) ; le reste de la portée du premier passage (inchangé, voir plus haut) ; le déplacement de la spec vers `specs/done/` encore non commité.

Bloquants : 0
Majeurs : 0
Mineurs : 1
- Le comportement « record invalide ignoré » (longueur > 64 ou non chaîne) n'est pas dit dans la description OpenAPI du paramètre.
