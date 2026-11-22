Remplacée par : 2026-11-22-carte-notification-toi-message.md

# Carte de notification : « Toi » quand l'expéditeur est le lecteur

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« Mettre "Toi" comme expéditeur pour les notifications où la personne n'est pas l'expéditeur », puis « Mettre "Toi" seulement s'il y a un message »)

## Décision

Sur la carte d'une notification, à la place de l'expéditeur, on affiche « Toi » (en : You, de : Du ; clé existante `notifications.you`). C'est le cas quand la notification est la copie de sa propre action (`own`, nouveau champ de l'API) ou quand l'expéditeur est le lecteur, et **seulement si elle porte un message**. « Toi » n'est pas un lien. Sans message, rien n'est affiché à la place de l'expéditeur.

## Motif

Le message joint (motif d'annulation ou de refus, message de la demande) est attribué à quelqu'un, y compris quand c'est soi. Sans message, le texte dit déjà « Vous avez… ».

## Portée

Les cartes de l'onglet Notifications (mobile). L'API sert `own` dans `NotificationResource`.

## Corollaires

`openapi.json` ne décrit pas les notifications : rien à régénérer.
