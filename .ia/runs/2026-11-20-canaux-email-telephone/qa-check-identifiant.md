RAPPORT QA — retouche : l'identifiant de connexion suit le canal unique
Go : oui
Base du verdict : rejoué moi-même : `ddev artisan test` = 227 passés (1088 assertions) ; `pint --test` = PASS, 280 fichiers ; `scramble:export` puis `diff` avec openapi.json = identique. Relu le diff et le trait `HasIdentifierField`. Cherché par grep les restes de code mort : `identifierLabel`, `identifierHelp` et `auth::user.identifier` n'ont plus d'usage hors du trait ; la clé `identifier_help` n'est utilisée que dans la branche des deux canaux.
Critères : 1 OK (branche `both()` : texte, aide, `max:255`). 2 OK (`Field::email`, sans aide ; test 422 sur un numéro saisi). 3 OK (`Field::phone` avec `countryOptions()`, `identifier_country` résolu via `Identifiers::resolve`, 422 sans pays, connexion et mot de passe oublié testés avec `079 123 45 67` + CH). 4 OK (règle dans le trait, pays dans `Identifiers::resolve`, méthodes d'`AuthChannels` retirées). 5 OK.
Ne couvre pas : l'app Expo (non modifiée, rendu des champs non rejoué), le rendu navigateur, la configuration `.env` réelle (canaux variés via les tests uniquement), et les comptes existants du canal désactivé (conséquence assumée dans la décision).

Bloquants : 0

Majeurs : 0

Mineurs : 1
- `Identifiers::toInternational` avale toute exception et renvoie la saisie brute si le numéro est invalide. Le résultat est alors « compte introuvable » plutôt qu'une erreur de format. C'est cohérent avec la réponse neutre du mot de passe oublié, mais non testé pour un numéro invalide avec pays.
