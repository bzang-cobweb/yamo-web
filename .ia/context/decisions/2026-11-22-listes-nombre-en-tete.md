# Mes logements, Mes réservations, favoris : le nombre sous les filtres

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« comme sur mes demandes reçues, mettre x logements sur mes logements, x réservations sur
  mes réservations, x logements dans mes favoris »)

## Décision

Comme « x demandes reçues » dans « Demandes reçues », en titre gras sous les filtres, en tête des cartes :
- « Mes logements » : « 3 logements », « 1 logement », « Aucun logement » ;
- « Mes réservations » : « 3 réservations », « 1 réservation », « Aucune réservation » ;
- favoris : « 3 logements »…
Le nombre suit la recherche et les filtres.

## Mécanique

`headingFor()` de chaque liste, avec `countFor()` ; clés `offer::offer.list.count` et `offer::booking.list.count`
(fr, en, de). L'app affiche déjà l'intitulé.
