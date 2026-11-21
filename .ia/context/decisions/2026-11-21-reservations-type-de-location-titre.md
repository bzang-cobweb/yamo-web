# « Mes réservations » : titre de la carte, type de location, type de logement et ville

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-21-reservations-vrai-titre.md, 2026-11-20-titre-carte.md

## Décision
Sur une carte de « Mes réservations », le titre est le type de location, le type de logement et
la ville, dans la langue du membre : « Location meublée · Studio · Douala ». « meublé » n'est
plus répété. Le domaine le sert (`Domain::bookingItemTitle()`, `null` : le titre construit
habituel). Les cartes n'ont pas de ligne au-dessus du titre : tout est dans le titre, côté
serveur, sans changement dans l'app.

## Motif
Demandes de l'humain du 2026-11-21 : « mes réservations cards mettre le type de location avant
le titre », puis « changer le titre par type de location type et ville ».

## Portée
« Mes réservations » seulement. Les cartes de « Réservations reçues », de la vitrine et de « Mes
logements » gardent leur titre.
