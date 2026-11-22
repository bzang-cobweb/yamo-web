# « Demandes reçues » : la date de la dernière notification sous le message

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« mettre la date de la dernière notification en dessous du message aligné à droite »)

## Décision

Sur une carte de « Demandes reçues », sous le message cité, alignée à droite, en petit et en gris : la date de
la dernière notification reçue par le propriétaire pour cette demande, dite comme sur les cartes de notification
(« il y a 2 h », « hier »…). Sans notification, rien.

## Mécanique

`ListItem::date()` sert `date` (ISO 8601, absente sans date) ; l'app la dit avec `relativeTime`.
