Remplacée par : 2026-11-22-carte-notification-date-apres-message.md

# Carte de notification : texte, puis expéditeur et date sur une ligne, puis message

Remplace : 2026-11-22-carte-notification-expediteur-apres-titre.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« Mettre l'expediteur après le message », puis « mettre la date sur la même ligne que l'expéditeur aligné à droite », puis « mettre le message après la ligne de l'expéditeur »)

## Décision

Ordre de la carte d'une notification dans l'app :

1. le type, en gras ;
2. le texte ;
3. une ligne : à gauche le nom de l'expéditeur, qui ouvre sa page (ou « Toi », voir 2026-11-22-carte-notification-toi) ; à droite la date ;
4. le message, en italique gris, sur une ligne.

## Motif

On lit ce qui s'est passé, puis qui l'a fait et quand, puis ce qu'il a écrit.

## Portée

Seulement les cartes de l'onglet Notifications (mobile, `NotificationsScreen`).

## Corollaires

Sans expéditeur, la ligne ne porte que la date, à droite. Le lecteur d'écran suit le même ordre : texte, nom, message.
