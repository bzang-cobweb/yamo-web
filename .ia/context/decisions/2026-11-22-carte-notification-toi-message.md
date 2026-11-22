Remplacée par : 2026-11-22-carte-notification-date-apres-message.md

# Carte de notification : « Toi » sans autre expéditeur, s'il y a un message

Remplace : 2026-11-22-carte-notification-toi.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« non, Mettre "Toi" comme expéditeur pour les notifications où la personne n'est pas l'expéditeur et qu'il y a un message »)

## Décision

Sur la carte d'une notification, quand aucune autre personne n'est affichée comme expéditeur et que la notification porte un message, la place de l'expéditeur affiche « Toi » (en : You, de : Du ; clé `notifications.you`). Ce n'est pas un lien. Peu importe pourquoi il n'y a pas d'autre expéditeur : copie de sa propre action, expéditeur qui est le lecteur, ancienne notification sans expéditeur.

Sans message, rien ne s'affiche à cette place.

## Motif

Le message affiché sur la carte est attribué à quelqu'un. Sans autre expéditeur, c'est toi.

## Portée

Les cartes de l'onglet Notifications (mobile). Le champ `own` envisagé pour l'API est retiré : l'app n'en a pas besoin.
