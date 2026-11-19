# `2026-11-19` — Contrat du champ `upload` et de l'étape « Photos », validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-19-champ-upload-photos.md` et quatre points à trancher.
- **Réponse** : « approved mais au moins une photo est obligatoire ».
- **Ce qui est retenu** :
  - nouveau type de champ générique `upload` (module Form) : types acceptés, taille maximale et nombre maximal déclarés par Laravel, servis à l'app, validés par le serveur avec les mêmes règles ;
  - envoi immédiat de chaque fichier vers `POST /api/v1/forms/{form}/uploads/{field}`, gardé comme fichier temporaire de son auteur, supprimé après 24 h s'il ne sert pas ;
  - 5ᵉ étape « Photos » du formulaire de logement : JPEG, PNG, WebP, 20 au plus, **au moins une photo obligatoire** ;
  - photos optimisées comme la photo de profil : 1600 px au plus, WebP, original non conservé ;
  - galerie seulement, aucune permission caméra ; aucune nouvelle dépendance.
- **Motif** : une annonce sans photo n'a pas d'intérêt pour un visiteur.
- **Portée** : modules Form et Offer (web), feature form (mobile).
- **Corollaires** : remplace le point 2 du contrat (« facultative au lancement »).
- **Tranché par** : humain
