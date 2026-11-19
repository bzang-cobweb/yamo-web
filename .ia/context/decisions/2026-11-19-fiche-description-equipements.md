# `2026-11-19` — Fiche d'une offre : description sans titre, libellé « Équipements » toujours visible ; équipements cochés à l'édition

- **Type** : décision
- **Question posée** : demandes directes de l'humain :
  - « quand j'édite une offre, les équipements de l'offre ne sont pas sélectionnés mais visibles dans le détail » ;
  - « enlever le label description quand on affiche la description de l'offre sur la page de détail » ;
  - « afficher le label équipements sur la page de détail de l'offre lorsqu'il y a au moins un équipement ».
- **Réponse** : les demandes elles-mêmes.
- **Ce qui est retenu** :
  - à l'édition, un attribut dont la valeur est une liste (les équipements choisis) est prérempli tel quel ; seules les valeurs rangées par langue sont des traductions (`Offer::detailIn`) ;
  - la fiche affiche la description sans le titre « Description » ;
  - dans les caractéristiques, le libellé garde sa place (45 % de la largeur au plus) et une valeur longue passe à la ligne, alignée à droite : « Équipements » ne disparaît plus derrière la liste. Sans équipement, la ligne n'apparaît pas (inchangé : le serveur ne la sert pas).
- **Motif** : demandes de l'humain ; le libellé disparaissait parce que seule la colonne du libellé pouvait rétrécir.
- **Portée** : `Offer::detailIn` (web) ; `OfferDetailScreen` (mobile).
- **Corollaires** : complète 2026-11-19-fiche-offre-ordre. Le texte `offer.description` reste servi à l'app, inutilisé par la fiche.
- **Tranché par** : humain
