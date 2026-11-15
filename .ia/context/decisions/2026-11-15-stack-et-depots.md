# `2026-11-15` — Stack du projet et découpage en deux dépôts

- **Type** : décision
- **Question posée** : quelles technologies pour l'application mobile compagnon du site, et comment organiser le code ?
- **Réponse** : application mobile en React Native avec Expo (iOS et Android), notifications push via le service Expo Push ; site et API en Laravel avec Sanctum (jetons), spatie/laravel-translatable (fr, en), spatie/laravel-medialibrary, Filament, nwidart/laravel-modules et spatie/laravel-permission. Deux dépôts : `yamo/web` (Laravel) et `yamo/mobile` (Expo).
- **Motif** : un seul développeur, venu du web (PHP, React) ; une base de code mobile unique pour les deux plateformes ; des cycles de version distincts pour le site et l'app.
- **Portée** : choix de stack et organisation des dépôts. Ne couvre ni le schéma métier ni l'hébergement.
- **Corollaires** : l'API Laravel est le seul point d'échange avec l'app ; les jetons des appareils (push) seront stockés côté Laravel ; Android exige un projet Firebase (clé FCM) et iOS un compte Apple Developer.
- **Tranché par** : humain
