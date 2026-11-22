# « Demandes reçues » : le total de la carte, celui du détail

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« demandes reçues le total n'est pas correct, le total du détail de la demande est correct »)

## Constat

La carte affichait le total enregistré de la demande ; le détail ajoute la caution d'un séjour (décision
2026-11-20-caution-dans-le-total) et, pour une visite, compte le loyer fois les mois de caution et d'avance.

## Décision

La carte affiche la ligne du total du détail, avec son libellé : « Total : 185 000 FCFA », ou « Total (sans
charges) : … » pour une visite avec charges ; rien sans total.

## Mécanique

`BookingResource::totalLine()`, une seule règle, sert le détail et la carte (`OfferRequestItem`). Clé
`offer::booking.list.total` : `:label : :amount`.
