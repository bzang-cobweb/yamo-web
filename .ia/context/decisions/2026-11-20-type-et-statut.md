# Notification : le type en gras, le statut de la demande dessous

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« dans tous les affichages d'une notification, afficher le type en gras et en dessous le statut du booking »)
- **Complète** : 2026-11-20-statut-reel.md, 2026-11-19-carte-notification

## Décision

- Sur la carte d'une notification, sur sa page et sur les cartes du calendrier de l'offre, sous qui agit :
  - le **type** en gras (« Demande de logement », le titre de la notification) ;
  - **dessous**, la pastille du **statut réel** de la demande.
- Un seul composant de l'app porte ce bloc : `NotificationType`.
- Le calendrier reçoit le type dans chaque carte (`type`).
- Sans qui agit (ancienne notification), l'en-tête ne montre plus le titre à la place du nom : le type est déjà dessous.
