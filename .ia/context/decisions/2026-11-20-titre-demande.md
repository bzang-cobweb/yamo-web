# Le titre de l'offre avant les dates d'une demande

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-heures-arrivee-depart.md, 2026-11-20-personnes-a-part.md

## Décision

Sur la page d'une notification d'une demande, avant les dates, le titre de l'offre en gras :
le type de logement, le type de location et la ville, séparés par « · », sans ceux qui
manquent (« Studio · Location meublée · Douala »).

Le serveur envoie les trois données rédigées dans `booking.offer`
(`property_type`, `rental_type`, `city`) ; l'app compose le titre. Aucune méthode n'est
ajoutée au domaine (`itemTitle()`, le titre des listes, n'a pas le type de location).

## Motif

Demande de l'humain du 2026-11-20 : « mettez le titre composé du type de l'offre, du type
de location, et de la ville avant check_in », puis : « n'ajoute rien, envoie ces données ».

## Portée

`BookingResource`, la page d'une notification dans l'app.
