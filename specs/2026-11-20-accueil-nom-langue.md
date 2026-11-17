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
