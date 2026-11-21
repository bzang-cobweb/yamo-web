# Notifications ouvertes depuis « Mes logements » : avec un retour

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-compteur-vers-notifications.md, 2026-11-21-compteur-non-lues-sans-statut.md, 2026-11-21-lien-notifications.md

## Décision

Les boutons d'une carte de « Mes logements » qui ouvrent les notifications (« x demandes en
attente », « x notifications non lues », « Notifications » du menu) ouvrent
`/notifications/filtered` : le même écran que l'onglet (`NotificationsScreen`, option `back`),
par-dessus les onglets, avec un en-tête « Notifications » et son bouton retour vers la carte.
Les filtres sont ceux de l'onglet (`notifications`) ; toucher l'onglet les vide toujours.

## Motif

Signalement de l'humain du 2026-11-21 : « quand je clique sur x demande en attente, ou sur x
notifications non lues ou sur notifications depuis mes logements cards je ne peux plus revenir
en arrière » — rejoindre l'onglet fermait « Mes logements », ouverte par-dessus les onglets.
Approche (un écran, deux routes) expliquée puis validée : « Oui, committe ».
