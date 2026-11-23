# Demande de visite : une seule date

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« le problème disparaît si on ne peut choisir qu'une date de visite ? », puis « oui, une seule date de visite »)

## Décision

Une demande de visite n'a plus qu'une date : le champ « Autre date (facultatif) » disparaît du formulaire. Une visite acceptée a donc forcément cette date, et il n'y a rien à choisir à l'acceptation. Si la date ne convient pas, le propriétaire refuse, avec un message pour proposer un autre jour. Le membre peut aussi modifier sa demande tant qu'elle est en attente.

- Le serveur ignore une seconde date envoyée (`check_out` d'une visite), à la création comme à la modification.
- Textes : « le 5 déc. 2026 », plus de « le … ou … ». Lignes de la demande : « Visite » seule, plus « Autre visite ».
- Calendrier : une visite ne colore que son jour.
- Les demandes existantes ne gardent que leur première date (migration `2026_11_30_230000`, lancée en local). Les notifications enregistrées ont été réécrites.

## Portée

`OfferBookingForm`, `BookingService::request` et `update`, `Booking::period` et `dateLines`, `OfferCalendar::visitsOn`, textes retirés (`period.visit_or`, `dates.second`, `form.visit_second_date`), commentaire de `DemoSeeder`.
