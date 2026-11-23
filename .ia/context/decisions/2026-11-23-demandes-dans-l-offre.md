# `2026-11-23` — Back-office : les demandes d'une offre sur sa fiche

- **Type** : décision
- **Question posée** : « les bookings sont une relation manager dans une offre pour laravel filament ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - onglet « Réservations (n) » sur la page d'une offre, avant « Avis » (`BookingsRelationManager`) ;
  - la table de la ressource « Réservations » (`BookingTable::configure($table, withOffer: false)`), sans la colonne ni le filtre « Offre » ;
  - en lecture seule, comme la ressource : « Voir » ouvre la fiche de la demande ; ni création, ni modification, ni suppression (`BookingPolicy`).
- **Motif** : la demande de l'humain.
- **Portée** : `OfferResource::getRelations()`, `BookingTable`.
- **Corollaires** : la ressource « Réservations » reste dans le menu, inchangée.
- **Tranché par** : humain
