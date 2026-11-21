# « Demandes » d'une offre : les notifications non lues de chaque demande

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« mettre notifications non lues »)

## Décision

Sur une carte de « Demandes » d'une offre, en bas, un compteur gris « N notifications non lues » : les
notifications non lues du propriétaire dans le fil de cette demande. Le toucher ouvre la page de la demande
(comme la carte), où elles se lisent. Sans notification non lue, pas de compteur.

## Mécanique

`ReceivedBookingListing` compte les notifications non lues par demande (`data->booking_id`). Le compteur ne
sert pas de filtre ; l'app n'efface alors pas les filtres de la liste nommée.

## Décidé seul (à confirmer)

L'endroit (compteur en bas de la carte, comme dans « Mes logements ») et la cible (la page de la demande).
