# Modifier sa demande tant qu'elle est en attente

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-19-contrat-reservations.md, 2026-11-21-reservations-menu.md

## Décision

- Le menu d'une carte de « Mes réservations » a une entrée « Modifier » tant que la demande est en
  attente. Elle ouvre le formulaire `offer.booking.edit` (`BookingEditForm`, les champs de la
  demande déjà remplis : dates, voyageurs pour un séjour, message), titré « Modifier la demande ».
- Envoi : `PUT /api/v1/bookings/{booking}` (ability `edit` : sa demande, sinon 404 ; en attente,
  sinon 403). Mêmes règles qu'une demande, sa propre demande exclue (pas de conflit avec
  elle-même) ; jamais sur un jour pris ou bloqué ; le total d'un séjour est recalculé au prix figé
  de la demande. Message : « Demande modifiée. »
- Le propriétaire n'est pas notifié de la modification (non demandé).

## Motif

Demande de l'humain du 2026-11-21 : « ajouter un lien pour modifier la réservation (édition
possible si statut encore en attente) ». Place dans le menu, prix figé, pas de notification au
propriétaire : décidé seul.
