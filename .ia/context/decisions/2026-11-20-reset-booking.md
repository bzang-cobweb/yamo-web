# « Reset booking » : remettre les demandes de démonstration

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active

## Décision

Le processus que l'humain appelle « reset booking » est la commande :

    ddev artisan booking:reset          # demande confirmation
    ddev artisan booking:reset --force  # sans confirmation

Elle vide toutes les demandes et toutes les notifications, puis relance `DemoSeeder`, qui
recrée les 9 demandes des visiteurs au membre de démonstration, à chaque étape, avec leurs
notifications (textes et règles du moment). Comptes, logements, photos et avis ne sont pas
touchés. Refusée en production.

Les demandes de démo couvrent les cas du texte d'une demande : une personne et plusieurs, une
visite avec et sans seconde date, un message de longueur maximale.

## Motif

Demande de l'humain du 2026-11-20 : « adapter demo en fonction du summary de la demande et
ensuite vider les bookings et les notifications et remettre les demandes de tests ; ceci
correspond au processus reset booking ».

## Portée

`app/Console/Commands/ResetBookingsCommand.php`, `DemoSeeder::BOOKINGS`.
