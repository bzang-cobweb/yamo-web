# Liens : la zone touchée limitée au texte

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« dans les cards, les liens sur les textes doivent se faire seulement sur le texte et non sur toute la ligne ; c'est le cas avec le clic sur le nom d'un membre »)

## Décision

Un lien posé sur un texte ne réagit qu'au texte lui-même, pas à toute la largeur de sa ligne. Toucher à côté du texte touche ce qu'il y a dessous, par exemple la carte, qui ouvre sa page. C'est corrigé pour :
- le titre en lien d'une carte de liste (le nom du membre dans les demandes reçues) ;
- le nom du logement en tête de la page d'une demande (toujours centré) ;
- le nom de l'expéditeur dans le suivi d'une demande.

Les autres liens étaient déjà limités au texte : sous-titre d'une carte, expéditeur d'une carte de notification, passages liés d'un texte.

## Portée

Mobile : `ListItemCard`, `NotificationDetailScreen`, `NotificationConversation`.
