# Démo : des demandes en attente qui se chevauchent

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-reset-booking.md

## Décision

Les demandes de démonstration (`DemoSeeder::BOOKINGS`, rejouées par `booking:reset`) comptent
deux séjours de plus, en attente, sur le « Studio meublé à Akwa », qui chevauchent la demande
déjà en attente et entre eux :

- visiteur 1 : arrivée J+10, départ J+13 (existante) ;
- visiteur 2 : J+11 → J+14, 2 personnes, avec un message ;
- visiteur 3 : J+12 → J+16, 1 personne, sans message.

Accepter l'une refuse les deux autres (motif de chevauchement). 11 demandes de démo au lieu de 9.
Les deux ont aussi été insérées dans la base locale, sans la réinitialiser.

## Motif

Demande de l'humain du 2026-11-21 : « insérer des exemples de demandes qui se chevauchent ».
Logement, visiteurs et dates : décidé seul.
