# `2026-11-27` — Une action de carte peut ouvrir un panneau de l'app

- **Type** : décision
- **Question posée** : « ajouter un lien statistique dans le menu qui ouvre un panneau avec le tableau vues et clics », puis « ouvrir membres dans un panneau ».
- **Ce qui est retenu** : une action de carte (`ListItem::action(..., panel: 'nom')`) peut servir `panel: {name, record}` ; le paquet form l'ajoute à la réponse seulement quand il est donné. L'app (`useItemActions`, `ProfileListScreen`) l'ouvre par-dessus la liste, dans un panneau à taille maximale titré du libellé de l'action, avec le contenu que son registre (`ActionPanel`) associe au nom ; un nom que cette version de l'app ne connaît pas n'ouvre rien. Panneaux connus : `partner.members` (`PartnerMembers`) et `partner.statistics` (`PartnerStatisticsPanel`).
- **Motif** : ouvrir un contenu sans quitter la liste, comme les formulaires en panneau, sans qu'un écran existe pour lui.
- **Portée** : web : `packages/form` (`ListItem`) ; mobile : type `ListItemAction.panel`, `useItemActions`, `ProfileListScreen`, `ActionPanel`.
- **Tranché par** : humain
