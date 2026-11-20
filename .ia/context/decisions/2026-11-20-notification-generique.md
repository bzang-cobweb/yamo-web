# NotificationResource reste générique ; le module d'un fil complète la page

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-suivi-demande.md, 2026-11-20-entete-premiere-etape.md

## Décision

- `NotificationResource` (module Notification) ne connaît plus les demandes : le champ
  `total` et sa fonction sont retirés.
- `withHistory()` devient `withThread()` : sur la page d'une notification, le suivi de
  son fil (`history`), puis, si la notification a un fil, l'événement
  `Modules\Notification\Events\NotificationThreadSerializing` (la notification, son fil,
  la réponse `data`, modifiable).
- Le module Offer l'écoute (`AddBookingToNotificationThread`) : un fil qui commence par
  `Booking::THREAD_PREFIX` (`booking:`) reçoit `data['booking']`, une `BookingResource`
  légère (id, offer_id, type, statut, dates, voyageurs, total dans la langue de la
  requête) ; `null` si la demande n'existe plus.
- `Booking::THREAD_PREFIX` et `Booking::thread()` sont la seule source du préfixe ; la
  migration de remplissage déjà exécutée garde sa chaîne écrite en dur.
- L'app lit le total dans `booking.total`.

## Motif

Demande de l'humain du 2026-11-20 : « NotificationResource doit être générique … enlever
la fonction total et tout ce qui a trait uniquement au booking … rajouter une function
withThread() … lancer un event qui peut modifier data … un listener dans le module Offer
… hydrater le data de l'event avec data['booking'] => un BookingResource léger ».

## Portée

La page d'une notification (`GET /notifications/{id}`). La liste ne lance pas l'événement.

## Corollaires

- `booking` est ajouté par un écouteur : Scramble ne le voit pas dans `openapi.json`.
- Les notifications enregistrées gardent `data.total` (figé à l'envoi) ; l'API ne le lit plus.
