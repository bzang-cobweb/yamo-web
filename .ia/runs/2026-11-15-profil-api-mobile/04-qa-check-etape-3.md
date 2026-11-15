# Verdict qa-check — étape 3 (projet mobile Expo)

- **Date** : 2026-11-15
- **Verdict** : GO — 0 bloquant, 0 majeur, 3 mineurs
- **Preuves rejouées par qa-check** (dépôt `yamo/mobile`) : `npm run typecheck` 0 erreur ;
  `npm run lint` sans problème ; `npx expo-doctor` 20/20 ; `.env` ignoré, `.env.example`
  documenté ; Expo web (http://localhost:8081) → 200 ; `POST /api/v1/auth/login` sans corps → 422 JSON ;
  routes et champs du client conformes à `openapi.json`.
- **Preuve de la session** (navigateur, Expo web, compte de test supprimé ensuite) : mauvais mot
  de passe → message de l'API affiché ; connexion → accueil en français (langue du compte, navigateur
  en anglais) ; rechargement → session conservée ; déconnexion → écran de connexion et jeton supprimé
  en base (1 → 0).

## Mineurs
- Comparaison au modèle create-expo-app non rejouable par qa-check → **vérifiée par la session**
  contre le `package.json` généré : ajouts = `@tanstack/react-query`, `expo-secure-store`,
  `eslint-config-expo` (dev) ; aucun retrait.
- 403 sur `/me` non traité → **corrigé** : 401 et 403 ramènent à la connexion.
- Déconnexion silencieuse si le serveur échoue → voulu (le jeton local est oublié quoi qu'il arrive).

## Ne couvre pas
**Simulateur iOS : aucun runtime iOS installé** (`xcrun simctl list runtimes` vide) — critère non
vérifié. Android, SecureStore sur appareil réel.
