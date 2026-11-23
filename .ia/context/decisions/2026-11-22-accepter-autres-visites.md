# Accepter une visite : avertir des autres visites du jour, et pouvoir les accepter aussi

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« si on accepte une demande de visite en chevauchement, accepter toutes les autres ? », puis « l'avertissement avec la case »)
- **Complète** : 2026-11-22-accepter-avec-message.md, 2026-11-22-calendrier-visites-chevauchement.md

## Décision

Quand d'autres visites du même logement sont en attente le même jour qu'une visite à accepter :
- le formulaire « Accepter la demande » avertit, sous le titre : « 2 autres visites sont demandées ce jour-là. » ;
- il propose une case, **décochée par défaut** : « Accepter aussi les 2 autres visites de ce jour ».

Sans cocher, les autres visites restent en attente, et le propriétaire répond à chacune. En cochant, elles sont acceptées en même temps, avec le même message facultatif, et chaque membre reçoit sa notification « Demande acceptée ».

Les séjours ne changent pas : ceux qui chevauchent sont toujours refusés automatiquement.

## Portée

`OfferAvailability::sameDayPendingVisits` (nouveau), `BookingAcceptForm` (avertissement et case), `BookingAnswerForm` (validation du champ `accept_other_visits`), `BookingController::answer` et `BookingService::answer` (nouveau paramètre). Textes `profile.accept_same_day_visits`, `answer.accept_other_visits`.
