# `2026-11-15` — Contrôleurs et services Login, Logout et Profile

Remplace : 2026-11-15-contrat-mobile-features-formulaires.md (arbitrage G uniquement ; le reste reste en vigueur)

- **Type** : décision
- **Question posée** : (demande de l'humain en cours de chantier) « Faire les contrôleurs et services Login, Logout et Profile côté Laravel et les services Login, Logout et Profile côté mobile ».
- **Réponse** :
  - **Laravel** : `AuthController` / `AuthService` sont scindés en `LoginController` + `LoginService` (`POST /api/v1/auth/login`), `LogoutController` + `LogoutService` (`POST /api/v1/auth/logout`), `ProfileController` + `ProfileService` (`GET` et `PATCH /api/v1/auth/me`). Les URL, les noms de routes, les gardes et les réponses ne changent pas.
  - **Mobile** : `AuthService` est remplacé par `LoginService` (`login`), `LogoutService` (`logout`) et `ProfileService` (`get` → `GET /me`, `update` → `PATCH /me`), une méthode par appel API, rangés dans `features/auth/services/`. L'écran Profil va dans `features/auth/screens/` : pas de feature `profile` séparée (l'arbitrage G (b) est remplacé).
- **Motif** : un contexte par classe — se connecter, se déconnecter, consulter et modifier son profil.
- **Portée** : module Auth (web) et feature auth (mobile).
- **Corollaires** : le contrat `openapi.json` ne change que par les noms d'opérations ; les tests de l'API de connexion restent valables tels quels.
- **Tranché par** : humain (interprétation de l'emplacement mobile par l'orchestrateur : `features/auth/services`)
