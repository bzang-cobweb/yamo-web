# Verdict qa-check — étape 3 (mobile : feature form, connexion et profil sur l'API, services Login/Logout/Profile)

- **Date** : 2026-11-15
- **Verdict** : GO — 0 bloquant, 0 majeur, 3 mineurs
- **Preuves rejouées par qa-check** : typecheck, lint, `npm test` 12/12 (2 suites), `expo-doctor` 20/20 ;
  `DynamicForm.test.tsx` : un champ de chaque type depuis une définition JSON, `hidden` non affiché
  mais envoyé, erreurs 422 sous les champs (`tags.1` compris), erreur sans champ en message général ;
  services conformes à `openapi.json` ; aucune validation côté mobile.
- **Preuves de la session** (Expo web, compte de test supprimé ensuite) : formulaire `auth.login` reçu
  de l'API (libellés, astérisques) ; envoi vide → erreurs de l'API sous chaque champ ; connexion →
  accueil ; Profil → `auth.profile.locale` (valeur actuelle) → English → l'app bascule (interface et
  formulaire de l'API) ; déconnexion puis reconnexion → anglais retrouvé, `locale = en` en base ;
  Entrée dans le dernier champ → envoi (régression corrigée en cours d'étape).

## Mineurs
- Pas de test automatisé de `FormLoader`, `ProfileScreen`, des services ni du changement de langue (prouvés par lecture et parcours navigateur).
- Type de champ inconnu ignoré avec un avertissement — non testé.
- `jest/` et la suppression d'`AuthService` à inclure dans le commit → fait (`c9e4c30`).

## Ne couvre pas
Rendu natif iOS/Android, qualité visuelle et accessibilité.
