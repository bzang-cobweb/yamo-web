# Une notification de demande par action

- **Date** : 2026-11-22
- **Type** : décision de conception
- **Décidé par** : l'humain (« revenir à la solution qui envoie un event différent en fonction de l'action : demande, annuler, refuser et accepter » ; choix « Revenir aux 4 classes » plutôt qu'afficher l'étape)
- **Remplace** : 2026-11-20-notification-demande-unique.md

## Décision
- `BookingRequested` devient la classe abstraite `BookingNotification`. Elle a une classe par action : `BookingRequestReceived`, `BookingRequestUpdated`, `BookingRequestAccepted`, `BookingRequestRefused`, `BookingRequestCancelled`.
- On crée une notification avec `BookingNotification::for($booking, $step, own, edited)`. `BookingStepEnum::notification()` donne la classe de chaque étape. `data.step` reste enregistré.
- Le type de la notification (`data.title`, et `card_title` lu à l'affichage) est celui de l'action (voir 2026-11-22-types-de-logement).
- Les notifications existantes sont migrées : `2026_11_30_190000_split_booking_requested_by_action` donne à chaque notification la classe de son étape. La migration se défait.
- `NotificationFilter::types()` donne, par clé, un libellé et une liste de classes (`types`).

## Motif
Le type de la carte dit l'action.

## Corollaires
- `card_subtitle` est retiré de l'API.
- Une notification encore en file sous `BookingRequested` ne serait plus traitée : vider la file avant un déploiement.
- « Demande modifiée » a aussi sa classe.
