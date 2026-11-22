# Mes logements : la carte ouvre la fiche, plus de « Voir » dans le menu

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« dans mes logements enlever le lien voir dans dropdown 3 petits points / le lien sur le card dirige vers détail de l'offre »)
- **Complète** : 2026-11-21-actions-en-menu.md

## Décision

Dans une liste avec un menu trois points (« Mes logements ») :

- toucher la carte ouvre l'écran de l'élément (la fiche du logement, brouillon compris), et plus son formulaire ;
- le menu ne propose plus « Voir ». « Éditer » y reste, avec le calendrier, les demandes et « Retirer ».

## Portée

Mobile (`ProfileListScreen`), règle générique : avec un menu et un écran, la carte ouvre l'écran. Sans menu, rien ne change : la carte ouvre le formulaire s'il y en a un, sinon l'écran. Côté serveur, seul un commentaire change.
