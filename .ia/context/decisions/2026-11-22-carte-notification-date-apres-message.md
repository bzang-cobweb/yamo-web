# Carte de notification : sans « Toi », la date après le message

Remplace : 2026-11-22-carte-notification-toi-message.md, 2026-11-22-carte-notification-expediteur-date-message.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« enlever l'expéditeur "Toi" », « mettre la date après le message »)

## Décision

Ordre de la carte d'une notification dans l'app :

1. le type, en gras ;
2. le texte ;
3. le nom de l'expéditeur, sauf si c'est nous ou s'il n'y en a pas (c'est un lien vers sa page) ;
4. le message, en italique gris, sur une ligne ;
5. la date, alignée à droite.

« Toi » n'est plus affiché.

## Portée

Les cartes de l'onglet Notifications (mobile, `NotificationsScreen`). La clé `notifications.you` reste : elle sert ailleurs, dans le suivi d'une demande.
