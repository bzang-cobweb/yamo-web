# Accepter une demande : le nombre des demandes qui se chevauchent, sans leurs noms

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« Accepter la … ? x autres demandes chevauchent ces dates et seront refusées. », « car le message risque d'être trop long si plusieurs dates »)
- **Complète** : 2026-11-19-contrat-calendrier-offre

## Décision

La confirmation avant d'accepter une demande qui en chevauche d'autres donne leur nombre, plus leurs noms ni leurs dates : « Accepter la demande de Marie Curie (du 3 déc. 2026 au 6 déc. 2026) ? 2 autres demandes chevauchent ces dates et seront refusées. » Avec une seule : « 1 autre demande chevauche ces dates et sera refusée. » En anglais : « … other request(s) overlap these dates and will be declined. » En allemand : « … weitere Anfrage(n) überschneiden sich … und werden abgelehnt. ».

## Motif

Avec plusieurs demandes, la liste des noms et des dates rendait le message trop long.

## Portée

`ReceivedBookingListing::acceptConfirm`, texte `profile.accept_overlaps` (fr, en, de).
