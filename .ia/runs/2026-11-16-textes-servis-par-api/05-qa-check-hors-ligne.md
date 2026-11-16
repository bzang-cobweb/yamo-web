RAPPORT QA — tâche 05, écran hors ligne permanent
Go : oui (borné)
Base du verdict : rejeu dans /Users/bertrandzang/home/private/yamo/mobile : npm test = 8 suites, 37 tests verts ; npm run typecheck = 0 erreur ; npm run lint = exit 0. Lecture du diff et des fichiers nouveaux (ConnectivityGate, connectivity.ts, _layout.tsx, tests, README). package.json : un seul ajout, expo-network ~58.0.5 ; node_modules/expo/bundledNativeModules.json (SDK 58, expo 58.0.25) impose ~58.0.5 ; version installée 58.0.5 ; lockfile cohérent.
Critères : 1 OK (offline = !useIsOnline() || échec de démarrage, écran hors ligne au-dessus) ; 2 OK (enfants jamais démontés, pointerEvents none, accessibilityElementsHidden, no-hide-descendants ; test de conservation de l'état) ; 3 OK par lecture (onlineManager.setEventListener sur addNetworkStateListener) ; 4 OK par lecture (état undefined = en ligne, testé) ; 5 OK (AuthProvider inchangé : rejet de jeton exclu de failedToLoad, donc connexion) ; 6 OK ; 7 OK (README mis à jour).
Ne couvre pas : coupure réelle d'internet (non jouée), appareil réel, Android, VoiceOver réel, reprise effective des requêtes en conditions réelles, comportement réel d'expo-network au démarrage (isInternetReachable inconnu).

Bloquants : 0

Majeurs : 0

Mineurs : 1
- Aucun test automatisé sur le câblage de RootNavigator ni sur bindQueriesToNetwork : critères 3 et 4 vérifiés par lecture et test unitaire de isOnline seulement.
