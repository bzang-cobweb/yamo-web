# `2026-11-26` — Annuler une demande demande un message

Complète : 2026-11-21-annuler-depuis-le-detail.md

- **Type** : décision
- **Question posée** : « rendre obligatoire le message dans le formulaire d'annulation ».
- **Ce qui est retenu** : le champ « Message » du formulaire d'annulation est obligatoire ; l'API refuse une annulation sans message, ou avec des espaces seulement (422), et la demande reste en l'état. L'indication du champ perd « (facultatif) » : « Expliquez pourquoi vous annulez… ».
- **Corollaire** : plus d'annulation sans message ; l'ancien cas « sans message (le menu de la carte) » n'existe plus, la carte n'ayant plus de menu d'annulation.
- **Motif** : le propriétaire sait pourquoi la demande est annulée.
- **Tranché par** : humain
