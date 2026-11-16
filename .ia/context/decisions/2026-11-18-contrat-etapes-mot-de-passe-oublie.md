# `2026-11-18` — Contrat du chantier formulaires à étapes et mot de passe oublié, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-18-etapes-mot-de-passe-oublie.md`
  (8 points, avec des choix par défaut).
- **Réponse** : « annuler le point 1 et je valide le reste ».
  - **Point 1 annulé** : l'e-mail reste affiché dans le profil.
  - **Réglages des codes** dans la config du module, clé `auth.verification_codes`,
    variables `AUTH_CODE_*`, défauts 10 min, 5 essais, 60 s.
  - **Ressource Régions** autonome supprimée : les régions se gèrent dans la fiche
    d'un pays (relation manager).
  - **En-tête de retour fixe**, libellé = titre de l'écran de départ ; dans l'étape de
    code, « Étape précédente ».
  - **Inscription en 3 étapes**, genre après le nom ; appel générique de validation
    d'une étape (`POST /api/v1/forms/{form}/steps/{n}`).
  - **Mot de passe oublié** :
    - identifiant inconnu traité comme un connu, sans rien envoyer ;
    - toutes les sessions sont déconnectées ;
    - retour à l'écran de connexion à la fin.
- **Motif** :
  - de longs formulaires découpés et validés au fil de la saisie ;
  - un accès au compte récupérable sans révéler qui est inscrit.
- **Portée** : modules Auth, Form et Location, app mobile.
- **Corollaires** : un formulaire sans étapes garde son comportement actuel.
- **Tranché par** : humain
