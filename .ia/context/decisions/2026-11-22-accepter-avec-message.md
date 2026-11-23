# Accepter une demande : un formulaire avec avertissement et message, comme le refus

Remplace : 2026-11-22-accepter-nombre-chevauchements.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« j'ai confirmé et la demande a été acceptée, pas moyen de mettre un message. Enlever donc cette confirmation et diriger vers le formulaire avec cet avertissement comme le fait le bouton refuser »)

## Décision

- « Accepter » n'envoie plus l'acceptation après une simple confirmation. Comme « Refuser », il ouvre un formulaire, « Accepter la demande » (`offer.booking.accept`).
- Sous le titre du formulaire : le membre et les dates. S'il y en a, l'avertissement suit : « 2 autres demandes chevauchent ces dates et seront refusées. », le nombre seul.
- Le formulaire propose un message facultatif pour le membre, puis le bouton « Accepter la demande ».
- Le message est gardé sur la demande (`bookings.acceptance_message`, nouvelle colonne). Il est joint à la notification « Demande acceptée » du membre, dans l'onglet, l'e-mail et le suivi, comme le motif d'un refus. Il est visible au back-office.
- Les demandes qui chevauchent sont toujours refusées à l'acceptation, avec le motif automatique d'avant.

## Portée

`BookingAcceptForm` (nouveau), `ReceivedBookingListing::withAnswerActions`, `BookingAnswerForm` (champ `acceptance_message` en validation), `BookingController::answer`, `BookingService::answer`, `BookingNotification::note`, et l'infolist du back-office. La route `POST /bookings/{booking}/accept` reste, mais l'app ne l'utilise plus. Migration `2026_11_30_220000`, lancée en local.
