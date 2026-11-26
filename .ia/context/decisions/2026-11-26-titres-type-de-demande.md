# `2026-11-26` — Le type de demande (séjour, visite) dans tous les titres d'une demande

- **Type** : décision
- **Question posée** : « pour les réservations mettre le type (visite, séjour) dans tous les titres (demande de visite envoyée, demande de séjour envoyée, …) ».
- **Ce qui est retenu** :
  - les titres d'une demande disent son type, en fr, en et de, par `:request` / `:Request` (« demande de séjour », « demande de visite », déjà utilisé par les SMS) :
    - la carte (`offer::booking.notify.types`) : « Nouvelle demande de séjour », « Demande de visite envoyée », « Demande de séjour acceptée »… ;
    - le suivi (`offer::booking.history`) : « Demande de visite refusée »… ;
    - le sujet de l'e-mail (`offer::booking.notify.<étape>.subject`) : « Demande de séjour annulée : « titre » » ;
  - `BookingNotification::title($step, $own, $type)` et `stepLabel($step, $type)` les construisent ; `OfferNotificationFilter` y passe le type de la demande, lu sur la demande ;
  - la notification enregistre `booking_type` (`stay`, `visit`) : le titre d'une demande supprimée garde son type ;
  - sans type connu (ancienne notification d'une demande supprimée) : « demande » seul, le titre d'avant (`request_lower.any`).
- **Motif** : distinguer un séjour d'une visite dès la carte, sans ouvrir la demande.
- **Portée** : notifications des demandes (onglet, suivi, e-mail).
- **Corollaires** : les notifications déjà enregistrées gardent leur `title` d'origine dans leurs données, mais la carte et le suivi recalculent le titre à l'affichage : ils montrent le type dès maintenant.
- **Tranché par** : humain (le type dans les titres) ; session (l'e-mail compris, `booking_type` enregistré, « demande » seul sans type)
