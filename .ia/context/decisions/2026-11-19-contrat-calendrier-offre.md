# Contrat du chantier calendrier d'un logement, validé

- **Type** : décision
- **Date** : 2026-11-19
- **Décidé par** : l'humain (« Approved »)

## Décision

Le contrat `specs/2026-11-19-calendrier-offre.md` est validé tel qu'écrit, y compris les quatre points tranchés par défaut :

1. Accepter une demande refuse automatiquement les demandes en attente qui la chevauchent, après une confirmation qui les nomme.
2. Le propriétaire peut bloquer des jours, un par un.
3. Le membre voit les jours indisponibles grisés dans une grille de mois, qui remplace le sélecteur natif dans le formulaire de réservation.
4. Une visite est un point sur son jour, sans créneau horaire.

## Motif

Traiter les réservations d'un logement dans le temps, et rendre impossible le chevauchement de deux séjours acceptés.

## Portée

Le module Offer (réservations, calendrier, jours bloqués), le module Form (champ « dates sur une grille »), et l'app (écran calendrier, champ).

## Corollaires

- Deux séjours acceptés ne se chevauchent jamais sur un même logement, que ce soit par l'app ou par l'API.
- Aucune dépendance ajoutée.
