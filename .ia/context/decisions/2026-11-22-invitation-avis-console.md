# Invitation à donner son avis : planifiée dans routes/console.php

Remplace : 2026-11-22-invitation-avis.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« ajoute la commande dans console »)

## Décision

La décision 2026-11-22-invitation-avis reste valable, sauf sur un point : la planification de `booking:invite-reviews` (chaque jour à 10:00) est déclarée dans `routes/console.php`, à côté de `model:prune`, et non plus dans `OfferServiceProvider::configureSchedules`. La commande reste inscrite par le module Offer.

## Risque assumé

Comme avant : le planificateur doit tourner (`schedule:run` en production). ddev ne le fait pas tourner en local.
