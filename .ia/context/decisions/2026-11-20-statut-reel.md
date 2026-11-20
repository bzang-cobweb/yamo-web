# Notifications : un seul type « Demande de logement », le statut réel de la demande

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« BookingRequestReceived, BookingRequestAccepted, … doivent être du même type "demande de logement" dans les filtres des notifications ; on doit se baser sur le statut réel du booking pour afficher le filtre statut et dans les cards des notifications et détail »)
- **Remplace** : 2026-11-20-statut-par-type.md, et le libellé par étape du filtre « Type » (2026-11-20-filtres-notifications)

## Décision

- **Le filtre « Type »** n'a plus qu'une valeur pour les quatre étapes d'une demande : « Demande de logement » (clé `booking`). Les modules inscrivent des groupes de types (`NotificationFilter::types()` : clé, libellé, classes).
- **La pastille d'une notification de demande**, sur la carte comme sur sa page, montre le **statut réel** de la demande (« En attente », « Acceptée », « Refusée », « Annulée ») avec sa couleur. Il est lu à l'affichage via `booking_id`.
  - Sans demande connue (ancienne notification, demande supprimée) : le statut que nomme l'étape notifiée.
- **Le filtre « Statut »** revient, sur ce statut réel. Il s'affiche, comme « Logement » et « Type de location », quand « Type » vaut « Demande de logement ».
- **Le calendrier** montre la même pastille de statut.
- **Le compteur de Mes logements** ouvre les notifications filtrées sur « Demande de logement », le statut « En attente » et ce logement. Son nombre et la liste concordent.
