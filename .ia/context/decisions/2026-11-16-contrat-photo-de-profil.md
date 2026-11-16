# `2026-11-16` — Contrat du chantier photo de profil, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-16-photo-de-profil.md` et quatre arbitrages préalables.
- **Réponse** :
  - **Dépendance mobile** : `expo-image-picker`, **galerie seulement** (pas d'appareil photo, aucune permission caméra).
  - **Mécanique** : composant dédié (photo touchable dans Profil) et routes `POST` / `DELETE /api/v1/auth/me/avatar` — **hors module `Form`**.
  - **Back-office** : Filament complet (champ dans le formulaire, vignette dans la liste, avatar de la barre du haut).
  - **Accès** : image publique, rangée sous un identifiant aléatoire (UUID), sans numéro séquentiel dans l'adresse.
  - **Avenant à la validation** : « il faut aussi optimiser la photo avant de l'enregistrer » — compris comme : le fichier d'origine n'est jamais conservé ; le téléphone recadre en carré et compresse avant l'envoi, le serveur réduit à 512 px au plus et réencode en WebP compressé avant d'enregistrer, avec les outils déjà installés (spatie/image, gd).
  - **Consigne permanente** : chaque ajout qui le nécessite se traite toujours dans les deux parties (web et mobile).
- **Motif** : photo de profil légère, simple à afficher partout, sans exposer d'adresses énumérables ni stocker d'originaux lourds.
- **Portée** : module Auth (web), feature auth (mobile) ; la consigne « deux parties » vaut pour tous les chantiers suivants.
- **Corollaires** : `expo-image-picker` est la seule dépendance autorisée pour ce chantier ; aucune file d'attente : conversions immédiates.
- **Tranché par** : humain (interprétation de « optimiser » par l'orchestrateur)
