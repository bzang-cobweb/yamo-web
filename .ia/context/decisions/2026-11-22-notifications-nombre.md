# Liste des notifications : le nombre sous les filtres

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« ajouter x notifications sur la liste des notifications »)
- **Complète** : 2026-11-22-listes-nombre-en-tete.md

## Décision

Sous la recherche et les filtres, en tête des cartes, la liste des notifications affiche son nombre en gras : « 12 notifications », « 1 notification », « Aucune notification » (en : notifications, de : Benachrichtigungen).

Le nombre compte les cartes, une par fil (une demande compte pour une), avec la recherche et les filtres. L'API le sert déjà rédigé dans chaque page (`count`).

## Motif

C'est la même chose que « x logements » et « x réservations ».

## Portée

`NotificationController::index`, `NotificationPageResource` (`count`) et `NotificationsScreen` (mobile). Cela coûte une requête `count` de plus par page.
