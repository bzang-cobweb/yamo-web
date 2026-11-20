Remplacée par : 2026-11-20-statut-reel.md

# Pastille d'une notification : le libellé de son type

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« in notification list, determine statut by notification type », puis « in fact use the label type as status »)
- **Complète** : 2026-11-19-carte-notification.md, 2026-11-20-filtres-notifications.md

## Décision

- **D'où vient la pastille** : l'API la déduit du **type** de la notification, à l'affichage. Elle ne la lit plus dans les données.
- **Ce qu'elle montre** :
  - le libellé : celui du type dans le filtre « Type » (« Demande de logement en attente », « … acceptée », « … refusée », « … annulée ») ;
  - la couleur : celle du statut (en attente orange, acceptée verte, refusée et annulée grises).
- **Qui la fournit** : le module qui inscrit le type (`NotificationFilter::tag()`).
- **Un type inconnu des modules** garde la pastille de ses données, s'il en a une.

## Motif

La pastille dit ce qu'est la notification, dans la langue de celui qui la lit, y compris pour les notifications anciennes, qui n'avaient pas de pastille.

## Corollaires

- Les données d'une notification de demande ne portent plus `tag`. `booking_status` garde le statut à l'envoi.
