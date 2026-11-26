# `2026-11-26` — Filtres des notifications : « Statut » et « Type de location » en puces

- **Type** : décision
- **Question posée** : « remplacer les filtres statut et type de location par des chips ».
- **Ce qui est retenu** :
  - dans les filtres des notifications, « Statut » (`status`) et « Type de location » (`rental_type`) sont des champs `chips` : un ou plusieurs choix ; « Type » reste une liste (2026-11-26-filtre-type-select) ;
  - « Statut » garde ses options selon le type choisi (une demande : En attente, Acceptée… ; un partenaire : En validation, En ligne…) ; plusieurs statuts gardent les notifications de chacun ; « En attente » reste sans les demandes expirées ;
  - chaque module ne lit que ses statuts (`OfferNotificationFilter`, `PartnerNotificationFilter`) ; une valeur seule (`filter[status]=pending`) marche encore ;
  - plusieurs types de location gardent les notifications des logements de chacun.
- **Motif** : choisir plusieurs statuts ou types de location d'un geste, comme les autres filtres en puces.
- **Portée** : la liste des notifications (serveur) ; l'app sait déjà afficher des puces dépendantes d'un autre champ.
- **Tranché par** : humain
