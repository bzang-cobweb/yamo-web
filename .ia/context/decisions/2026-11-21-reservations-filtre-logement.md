# Mes réservations : filtre « Logement »

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« ajouter un filtre logement dans les filtres de mes réservations »)

## Décision

Les filtres de « Mes réservations » gagnent une liste déroulante « Logement », après la ville et
avant les types de l'offre : les logements que le membre a demandés dans le pays courant
(supprimés compris), libellés par le titre de leur carte (le titre saisi, à défaut la ligne du type).
Choisir un logement ne garde que ses demandes.

## Motif

Retrouver d'un coup toutes ses demandes pour un même logement, comme le propriétaire le fait déjà
dans « Demandes reçues ».

## Portée

`BookingListing` (serveur) ; l'app affiche les filtres servis, sans changement.

## Corollaires

Le libellé est celui de `offer::booking.profile.filter_offer`, partagé avec les demandes reçues.
