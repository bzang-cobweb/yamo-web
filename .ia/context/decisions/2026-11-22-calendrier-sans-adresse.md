# Calendrier d'un logement : plus de bouton « Afficher / Masquer l'adresse »

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« enlever le bouton masquer adresse dans calendrier »)
- **Complète** : 2026-11-22-calendrier-carte-demandes-recues.md

## Décision

Les cartes du panneau du jour n'ont plus que « Accepter » et « Refuser », tant que la demande attend une réponse. Le bouton « Afficher / Masquer l'adresse » disparaît du calendrier. Une demande acceptée, refusée ou passée n'a plus de bouton.

## Portée

`ReceivedBookingListing::withAnswerActions`, que seul le calendrier utilise. Les routes `api.v1.bookings.address.show` et `hide` restent.
