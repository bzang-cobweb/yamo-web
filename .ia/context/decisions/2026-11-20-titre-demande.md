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

Après la pastille du statut, le nom de l'offre (son titre saisi, `booking.offer.title`) en
gras, en lien vers sa fiche ; le titre composé n'est pas en gras. Demandes de l'humain :
« afficher le nom de l'offre sous forme de lien après la pastille du statut », « mettre ce
titre en gras et le titre composé enlever le gras ».

**Puis retiré** (demande de l'humain : « enlever le summary et le titre composé ») : la
page d'une notification d'une demande n'a plus ni texte de la demande ni titre composé ;
après la pastille, le nom de l'offre en lien, puis les lignes (dates, durée, personnes, prix,
total) ; après le nom, le type de location (« mettez le type de location après le titre »).
`booking.offer` garde `title` et `rental_type`. Une notification sans demande garde son texte.

**Mise en page finale de la zone fixe** (demandes successives de l'humain) : le type de
location en gras à gauche et la pastille à droite ; le nom de l'offre en lien, en gras ; le
nombre de personnes à gauche et le nombre de chambres du logement à droite (`booking.offer.bedrooms`,
rédigé) ; les dates ; la durée à gauche et le prix à droite ; le total en gras.

## Motif

Demande de l'humain du 2026-11-20 : « mettez le titre composé du type de l'offre, du type
de location, et de la ville avant check_in », puis : « n'ajoute rien, envoie ces données ».

## Portée

`BookingResource`, la page d'une notification dans l'app.
