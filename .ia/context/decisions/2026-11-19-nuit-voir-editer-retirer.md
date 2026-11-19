# `2026-11-19` — « Mes logements » : Voir, Éditer, Retirer

- **Type** : décision
- **Question posée** : demande directe de l'humain pendant la nuit : « depuis mes logements boutons voir, éditer et retirer ».
- **Réponse** : la demande elle-même ; la forme est choisie par la session.
- **Ce qui est retenu** :
  - les items de « Mes logements » servent aussi un lien vers leur fiche (`link` : `/offers/{id}`), en plus du formulaire (`edit`) et du retrait (`delete`) ; le propriétaire voit la fiche de ses brouillons et archives (décision 2026-11-18-fiche-offre) ;
  - règle générique de l'app : un item qui a un écran **et** un formulaire affiche, sur la ligne des actions sous le contenu, « Voir », « Éditer » et « Retirer » ; toucher la carte ouvre toujours le formulaire ;
  - « Voir » et « Éditer » sont des textes de l'app (`list.view`, `list.edit`).
- **Motif** : demande de l'humain.
- **Portée** : `OfferListing` (lien), `ListItem` (documentation) ; `ProfileListScreen` (mobile).
- **Corollaires** : complète 2026-11-19-nuit-retirer-sous-image (la ligne des actions).
- **Tranché par** : humain (forme par la session de nuit)
