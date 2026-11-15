# Verdict qa-check — étape 2 (mobile : features/auth, shared, langue de l'appareil)

- **Date** : 2026-11-15
- **Verdict** : GO — 0 bloquant, 0 majeur, 3 mineurs
- **Preuves rejouées par qa-check** : typecheck, lint, `npm test` 7/7, `expo-doctor` 20/20 ;
  arborescence `features/auth/*` et `shared/*`, anciens emplacements absents, déplacements
  détectés en renommages ; dépendances ajoutées = celles autorisées ; `GET /api/v1/locales` réel.
- **Preuve de la session** (Expo web, compte de test) : navigateur `en-GB, fr-CH, it-CH` → écran
  de connexion en anglais ; connexion → accueil en français (langue du compte) ; rechargement →
  session conservée ; déconnexion → jeton supprimé en base (0).

## Mineurs
- Réindentation de `package.json` / `app.json` → **corrigé** (2 espaces, format Expo) avant le commit.
- API injoignable → repli sur la langue par défaut de l'app (`fr`) : voulu.
- Le chargement attend la liste des langues (1 nouvel essai) : écran de démarrage plus long si l'API est lente — non traité.

## Ne couvre pas
Repli « API injoignable » et provider sans test automatisé ; appareil iOS/Android réel.
