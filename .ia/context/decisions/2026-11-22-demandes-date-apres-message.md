# Demandes reçues : la date après le message

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« cards des demandes reçues mettre la date après le message »)
- **Complète** : 2026-11-22-demandes-message-apres-texte.md, 2026-11-22-demandes-date-derniere-notification.md

## Décision

Sur la carte d'une demande reçue, la date de la dernière notification quitte la ligne du nom. Elle vient sur sa propre ligne, après le message, alignée à droite, comme sur les cartes de notification. L'ordre est donc : le nom, le texte (type et dates), le message et la pastille des non lues, puis la date.

## Portée

`ProfileListScreen` (mobile), dans les deux mises en page de carte. Seules les demandes reçues ont une date (`date`).
