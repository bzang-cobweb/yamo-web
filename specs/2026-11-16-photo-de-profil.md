# Contrat — Photo de profil (web et mobile)

- **Chantier** : `2026-11-16-photo-de-profil`
- **Demande** (chat, 2026-11-16) : « Ajouter la possibilité à un utilisateur
  d'ajouter une petite photo sur son profil. Traiter la partie web et mobile. »
  Consigne permanente ajoutée : chaque ajout qui le nécessite se traite toujours
  dans les deux parties.
- **Arbitrages tranchés** (chat, 2026-11-16) : galerie seulement
  (`expo-image-picker`, sans appareil photo) ; composant dédié, hors module `Form` ;
  Filament complet ; image publique sous une adresse non devinable.
- **Statut** : validé le 2026-11-16, avec avenant : optimiser la photo avant de l'enregistrer
  (décision `2026-11-16-contrat-photo-de-profil.md`)

## Ce que j'ai compris

### Web (yamo-web)

- **Stockage** : collection medialibrary `avatar` sur `User`, **un seul fichier**
  (une nouvelle photo remplace l'ancienne). Disque `public`, rangé sous un
  identifiant aléatoire (UUID) au lieu du numéro de ligne : une adresse ne permet
  pas d'en deviner une autre.
- **Formats acceptés** : JPEG, PNG, WebP (et HEIC si gd/imagick le décodent, sinon
  refusé proprement) ; **5 Mo** maximum.
- **Optimisation avant enregistrement** (avenant) : l'original n'est jamais
  conservé. Le serveur réduit l'image reçue à 512 px de côté au plus et la
  réencode en WebP compressé, puis n'enregistre que ce fichier.
- **Conversion** : une vignette carrée **256 × 256** (recadrée au centre, WebP),
  générée **immédiatement** — le projet n'a pas de file d'attente qui tourne, une
  conversion mise en file ne serait jamais produite.
- **API** (module Auth, jeton requis, chacun sur son propre compte) :

  | Route | Rôle |
  |---|---|
  | `POST /api/v1/auth/me/avatar` | Envoi multipart du fichier `avatar` ; renvoie l'utilisateur à jour |
  | `DELETE /api/v1/auth/me/avatar` | Retire la photo ; renvoie l'utilisateur à jour |

  Gardes `can:` comme les autres routes du profil ; erreurs de validation en 422,
  traduites. Le service `ProfileService` porte l'ajout et le retrait.
- **`UserResource`** gagne `avatar_url` (vignette) — `null` sans photo. L'adresse
  suit l'hôte de la requête (le simulateur parle en HTTP à DDEV, un lien HTTPS ne
  s'y afficherait pas).
- **Back-office Filament** : champ photo dans le formulaire utilisateur (création
  et édition), vignette ronde dans la liste, photo en avatar dans la barre du haut
  (initiales sans photo, comme aujourd'hui).
- Supprimer un compte supprime sa photo (comportement medialibrary, à prouver).
- `openapi.json` régénéré.

### Mobile (yamo-mobile)

- Dépendance **`expo-image-picker`** (`npx expo install`), galerie seulement :
  aucune permission caméra déclarée.
- `ProfileService` gagne `uploadAvatar(file)` et `removeAvatar()` (une méthode par
  appel API) ; `ApiClient` apprend à envoyer du multipart.
- Composant `Avatar` (photo ronde, initiales sans photo) affiché sur l'Accueil et
  dans Profil. Dans Profil, toucher la photo propose **Choisir une photo** /
  **Retirer la photo** ; recadrage carré sur le téléphone ; envoi, puis mise à jour
  de l'utilisateur affiché. Erreurs de l'API affichées sous la photo.
- Textes fr/en.

## Découpage

1. Web : stockage, API, ressource, Filament, tests → `qa-check` → commit `yamo-web`.
2. Mobile : dépendance, service, composant, écrans, tests → `qa-check` → commit
   `yamo-mobile`.
3. Vérification bout en bout dans le simulateur iOS.

## Critères de succès

1. Depuis l'app, un utilisateur connecté choisit une image de sa galerie : elle
   apparaît, ronde, dans Profil et sur l'Accueil, et reste après redémarrage de l'app.
2. Il la retire : ses initiales reviennent, et le fichier n'existe plus sur le disque.
3. Une nouvelle photo remplace l'ancienne : un seul fichier par compte sur le disque.
4. Un fichier non image ou de plus de 5 Mo est refusé avec un message traduit, sans
   rien changer à la photo existante.
5. Sans jeton : 401 ; personne ne change la photo d'un autre par l'API.
6. Dans le back-office, un administrateur autorisé voit et change la photo d'un
   utilisateur ; la barre du haut montre la photo du compte connecté.
7. L'adresse d'une photo ne contient pas de numéro séquentiel.
8. Supprimer un compte supprime sa photo.
10. Le fichier enregistré est un WebP de 512 px de côté au plus, nettement plus
    léger qu'une grande photo envoyée ; l'original n'est stocké nulle part.
9. Tests web et mobile, typage, lint, Pint au vert ; `RouteGuardTest` passe sans
   nouvelle exception.

## Hors périmètre

Appareil photo, photo sur la future partie publique du site, modération des images,
plusieurs photos par compte.
