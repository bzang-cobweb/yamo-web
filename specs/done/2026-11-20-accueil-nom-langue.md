# Accueil : nom complet, logo sur les onglets seulement, choix de la langue

Demande du 2026-11-20, trois points.

## Ce que j'ai compris

1. **Onglet Accueil (connecté)** : le nom complet de l'utilisateur (`full_name`) s'affiche sous l'avatar.
2. **Logo** : la barre fixe du haut, avec le logo, reste sur les écrans racines des onglets (Accueil, Profil). Elle disparaît des sous-pages, c'est-à-dire des formulaires du profil (informations personnelles, e-mail, téléphone, mot de passe, préférences). Ceux-ci gardent leur en-tête avec le bouton retour.
3. **Choix de la langue sur l'écran d'accueil de l'invité** (logo, description, boutons de connexion et d'inscription) :
   - un sélecteur compact affiche les langues proposées par l'API (`GET /api/v1/locales`), avec la langue courante marquée ;
   - choisir une langue change aussitôt les textes de l'app et la description de l'application ;
   - le choix est mémorisé sur l'appareil, il survit à un redémarrage et prime sur la langue de l'appareil tant qu'on n'est pas connecté ;
   - une inscription faite ensuite crée le compte dans cette langue (l'API retient déjà la langue de la requête) ;
   - une fois connecté, la langue du compte prime, comme aujourd'hui.
   - Aucune nouvelle dépendance : le choix est rangé dans le stockage déjà utilisé pour le jeton (`expo-secure-store`).

## Critères de succès

- L'onglet Accueil affiche l'avatar puis le nom complet.
- La barre du logo est visible sur Accueil et Profil, absente sur chaque formulaire du profil.
- Sur l'écran d'accueil de l'invité, toucher une autre langue change la langue des boutons et de la description, sans rechargement.
- Après un redémarrage de l'app, la langue choisie est toujours appliquée.
- Un compte créé après ce choix a cette langue.
- Tests mobile et web verts, tsc et lint propres.

## Ajout à la validation

- **Page web `/`** : des liens de langue (FR / EN) changent la langue de la page, notamment la description. Le choix est gardé dans la session du navigateur.

## Ce que j'ai décidé seul

- La langue choisie par l'invité passe en tête des langues de l'appareil, et le choix final reste fait par `pickBestLocale`. Une langue mémorisée que l'API ne propose plus est donc ignorée.
- Le stockage de l'appareil est domicilié dans `shared/storage/device-storage.ts`, partagé par le jeton et la langue, au lieu d'une seconde copie du code propre à chaque plateforme.
- `chooseLocale` vit dans le contexte de traduction partagé (`I18nContext`), pour que l'écran d'accueil (feature welcome) ne dépende pas de la feature auth.
- Le sélecteur ne s'affiche pas si l'API ne propose qu'une langue.
- Web :
  - choix gardé en session par `GET /locale/{code}` (404 pour une langue inconnue) ;
  - middleware `SetSessionLocale`, limité aux pages publiques ;
  - liens en haut à droite, chacun dans sa langue.
- J'ai ajouté `Accept-Language: fr` à ton test `test_home_page_shows_the_logo_files_and_the_description`. La page suit désormais la langue du navigateur, et le client de test envoie l'anglais par défaut.

## Ce qui a été fait

- Mobile :
  - nom complet sous l'avatar ;
  - barre du logo masquée sur les sous-pages du profil, qui retrouvent la zone sûre du haut ;
  - `LocaleSwitcher` sur l'écran d'accueil de l'invité ;
  - langue choisie mémorisée et prioritaire avant connexion ;
  - tests `HomeScreen`, `LocaleSwitcher` (3) et `AuthProvider` (langue, 3).
- Web :
  - liens de langue sur `/` ;
  - session, puis navigateur, puis défaut ;
  - 4 tests ;
  - un test qui vérifie que l'inscription crée le compte dans la langue de la requête.
- Vérifications :
  - mobile : 91 tests, tsc et lint propres ;
  - web : 201 tests, Pint propre ;
  - qa-check : GO, 0 bloquant ;
  - simulateur : nom complet sur Accueil, barre sur Accueil et Profil, absente sur Préférences ;
  - navigateur : la page `/` passe de l'anglais au français, avec la description.

## Ce que je n'ai pas vérifié

- Le sélecteur de langue de l'écran d'accueil à l'écran. Le simulateur est connecté à ton compte, que je n'ai pas déconnecté, et la version web de l'app ne démarre pas (voir plus bas). Il est couvert par les tests seulement.
- Le redémarrage de l'app avec une langue mémorisée sur un appareil. Seul un test le couvre.
- Le logo de la page `/` en HTTP : l'adresse des images est en HTTPS, et le navigateur intégré refuse le certificat DDEV. C'est sans lien avec ce chantier.

## Ce qui reste en suspens

- La version web de l'app mobile (`expo start`, puis web) plante au rendu serveur : `expo-network` lit `window`. C'est antérieur à ce chantier.
- `TranslatableTabs` passe de `contained(false)` à `contained(true)` : c'est une modification de l'humain, non commitée, hors de ce chantier.
