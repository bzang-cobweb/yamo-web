# Le message du membre cité sur la carte d'une demande reçue

- **Type** : décision de conception
- **Date** : 2026-11-19
- **Décidé par** : l'humain (« Yes, on the cards », en réponse à « afficher aussi le message du membre sur les cartes des demandes ? »)
- **Complète** : 2026-11-19-notification-message-et-offre.md

## Décision

Sur la carte d'une demande reçue, le message saisi par le membre est cité en italique, avec un filet à gauche, sous le sous-titre (membre, type, dates, voyageurs). Il n'apparaît que si le membre en a saisi un.

- **Serveur** : `ListItem::quote()` est générique (champ `quote`, `null` sans message). Les espaces sont resserrés et le texte est tronqué à 300 caractères. `ReceivedBookingListing` l'utilise avec `Booking::message`.
- **App** : la citation est un composant partagé, `Quote`, utilisé par les cartes de liste et par les notifications.

## Motif

Le propriétaire lit le message du membre au moment de répondre (Accepter, Refuser), sans quitter la carte.

## Portée

Les demandes reçues uniquement. Mes réservations ne cite ni le message du membre (c'est le sien) ni le motif d'un refus.

## Corollaires

- Toute liste peut citer un message saisi avec `->quote()`, sans toucher à l'app.
