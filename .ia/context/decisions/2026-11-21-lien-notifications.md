# « Notifications » dans le menu d'une carte de « Mes logements »

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-21-actions-en-menu.md, 2026-11-21-compteur-non-lues-sans-statut.md

## Décision

Le menu d'une carte de « Mes logements » a une entrée « Notifications », juste après
« Calendrier ». Elle ouvre les notifications filtrées sur le type demande de logement et ce
logement — les filtres de « x notifications non lues », sans « Non lues ».

Une action de carte peut porter des filtres (`ListItem::action(..., filter:, list:)`, servis en
`list` et `filter`) : l'app les choisit, puis rejoint l'écran `link`, comme un compteur.

## Motif

Demande de l'humain du 2026-11-21 : « ajouter un lien notifications après calendrier qui a les
mêmes filtres que x notifications non lues mais sans le filtre non lues ».
