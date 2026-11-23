# Page d'une demande : « Logement » avec la ville ; « Disponible dès le » à sa place

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« ne pas déplacer disponible dès / ajouter la ville dans le titre composé »)
- **Complète** : 2026-11-22-detail-lignes-titre-dates-adresse.md

## Décision

- La ligne « Logement » donne le titre composé avec la ville : « Location meublée · Studio · Douala », comme sur les cartes. Sans ville, il n'y en a pas.
- « Disponible dès le » ne bouge pas : il reste après les personnes, où il était avant le déplacement des dates. Les lignes se lisent donc : Logement, dates (Arrivée, Départ ou Visite), Adresse, contact, Personnes, Disponible dès le, Durée, prix, Total.

## Portée

`BookingResource::lines` (`OfferCardTitle::of(...)[1]`).
