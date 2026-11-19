# `2026-11-19` — Croix « Fermer » dans l'en-tête des sous-écrans

- **Type** : décision
- **Question posée** : « ajouter une croix sur la barre du haut à droite pour fermer l'écran si navigation de plus d'un écran ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - l'en-tête des sous-écrans (`BackHeader`, app) affiche une croix à droite quand l'écran de départ est à plus d'un écran : piles imbriquées comprises (pile principale et pile de l'onglet ouvert) ; les étapes d'un formulaire ne comptent pas ;
  - la croix ramène d'un coup à l'écran de départ (chaque pile revient à son premier écran) ; la flèche garde son rôle (étape ou écran précédent) ;
  - libellé « Fermer » (`nav.close`), servi par l'API en fr, en, de.
- **Motif** : la demande de l'humain.
- **Portée** : app (`BackHeader`, `useClose` dans `shared/navigation/BackStack.tsx`) ; Laravel (`lang/*/mobile.php`).
- **Corollaires** : fermer un formulaire en cours abandonne sa saisie, sans confirmation (comme la flèche).
- **Tranché par** : humain (demande) ; périmètre (écrans seulement, sans confirmation) par défaut de l'agent
