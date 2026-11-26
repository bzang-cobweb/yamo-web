# `2026-11-26` — Depuis la fiche d'une offre, donner ou modifier son avis dans un panneau à sa hauteur

Complète : 2026-11-20-boutons-avis-fiche.md, 2026-11-26-message-editeur-en-panneau.md

- **Type** : décision
- **Question posée** : « page de détail offre formulaire ajouter et modifier un avis aussi dans le panneau avec la hauteur adaptée au contenu ».
- **Ce qui est retenu** : « Donner mon avis » et « Modifier mon avis », dans la section Avis comme dans la barre du bas, ouvrent le formulaire `offer.review` dans un `BottomSheet` à la hauteur du formulaire, au lieu de l'écran `/offers/{id}/review`. Envoyé : le panneau se ferme, le message s'affiche, la fiche et les listes se rechargent. « Supprimer » garde sa confirmation.
- **Corollaire** : l'écran `/offers/{id}/review` reste, ouvert par « Laisser un avis » depuis la page d'une demande terminée.
- **Tranché par** : humain
