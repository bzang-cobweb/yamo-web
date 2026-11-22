Remplacée par : 2026-11-22-notification-par-action.md

# Une seule notification de demande, `BookingRequested`, avec son étape

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« remplacer toutes ces notifications BookingRequestXXXX par une seule notification BookingRequested avec un paramètre qui indique si c'est Received, Accepted, … ; comme ça le filtre type peut se faire simplement sur la table des notifications »)
- **Complète** : 2026-11-20-statut-reel.md (le statut affiché reste le statut réel de la demande)

## Décision

- Les quatre classes `BookingRequestReceived`, `…Accepted`, `…Refused`, `…Cancelled` sont remplacées par **`Modules\Offer\Notifications\BookingRequested`**, construite avec la demande et son **étape** (`BookingStepEnum` : `received`, `accepted`, `refused`, `cancelled`).
  - L'étape choisit les textes (`offer::booking.notify.<clé>`), le destinataire de la liste (`received` / `mine`) et le message joint.
  - Elle est enregistrée dans **`data.step`**.
- **Le filtre « Type »** porte sur la seule colonne `type` : `booking` ⇒ `type = BookingRequested`. Un module inscrit, par type, un libellé et **une** classe (`NotificationFilter::types()`).
- **Les notifications existantes** sont migrées (`2026_11_30_130000_merge_booking_notifications_into_booking_requested`) : nouveau type, `step` ajouté depuis l'ancienne classe. La migration se défait.
- La pastille, quand la demande n'existe plus, prend le statut que nomme l'étape (`BookingStepEnum::status()`).

## Corollaire

- La notification porte le même nom court que l'événement `Modules\Offer\Events\BookingRequested` : là où les deux servent, la notification est importée sous l'alias `BookingRequestedNotification`.
- Une notification encore en file sous une ancienne classe ne serait plus traitée : sans effet en local (file synchrone), à vider avant un déploiement.
