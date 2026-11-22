Remplace : le point devant le nom (2026-11-22-demandes-point-non-lu, annulé par revert)

Remplace : 2026-11-22-demandes-point-non-lu.md (annulée)

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« annuler le point précédent et mettre la pastille avec le nombre de notification non lue
  à droite du message »)

## Décision

Sur une carte de « Demandes reçues », sur la ligne du message cité, à sa droite : une pastille rouge avec le
nombre de notifications non lues de la demande (« 2 »). Sans non lue, pas de pastille. Lu par l'accessibilité :
« Notifications non lues : 2 ». Le point devant le nom est retiré.

## Mécanique

`ListItem::unreadCount()` sert `unread_count` (absent à 0) ; l'app met la pastille à droite du message. Clé app
`notifications.unreadCount`.
