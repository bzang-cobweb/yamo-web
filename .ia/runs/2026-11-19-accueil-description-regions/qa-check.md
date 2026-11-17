RAPPORT QA — 2026-11-19-accueil-description-regions
Go : oui (avec 1 majeur et 1 point non rejoué)
Base du verdict : rejoué moi-même
- web : `ddev artisan test --compact` = 188 passed (892 assertions) ; `pint --test` = PASS, 262 fichiers ; composer.json/composer.lock inchangés (git diff vide).
- mobile : `npx jest` = 21 suites, 82 tests passed ; `tsc --noEmit` exit 0 ; `eslint src` exit 0 ; package.json/package-lock.json inchangés.
- lecture du code/tests : Field::textarea() max_length 1000 par défaut, règle max serveur, max_length dans toArray ; tests 1001 caractères refusé sur PATCH personal-info ; route publique v1/app ; colonne regions_count (->counts('regions'), un seul withCount) ; page Filament SettingResource ; fichiers app welcome.tsx / features/welcome / TextareaField modifiés.
Ne couvre pas : rendu visuel (simulateur, centrage de la connexion, compteur n/N, écran Accueil, page web `/`), rendu Filament réel, navigation retour/connecté (seulement les tests jest), régénération d'openapi.json (voir ci-dessous).

Bloquants : 0

Majeurs : 1
- Critère 6 : openapi.json ne contient pas `max_length` (0 occurrence). `/v1/forms/{form}` décrit `fields` comme `array` d'items `{}` non typés (tous les attributs de champ y sont absents, pas seulement max_length). `/v1/app` et `description` utilisateur y figurent bien. Le critère « openapi reflète max_length » n'est donc pas tenu littéralement ; à trancher (typer les items de champ, ou assouplir le critère).

Mineurs : 1
- `ddev composer openapi` et `ddev artisan scramble:export` échouent chez moi : « Failed to parse dotenv file. Encountered unexpected whitespace at [Ya Mo] » (valeur du .env non entre guillemets, environnement local ; .env non lisible par la QA). Je n'ai donc pas pu vérifier que openapi.json est à jour de la génération ; vérifié seulement par lecture (présence de /v1/app et des description). Les tests, eux, passent.
