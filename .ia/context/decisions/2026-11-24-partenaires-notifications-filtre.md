# `2026-11-24` — « Notifications » : retrouver celles des partenaires, par statut

- **Type** : décision
- **Question posée** (mode nuit) : « pages notifications : retrouver toutes les notifications générées aux actions sur des partenaires, trier par status ».
- **Réponse** : la demande, lue ainsi : « trier par status » = filtrer par statut ; la liste reste triée par date, comme pour les demandes de logement.
- **Ce qui est retenu** :
  - `PartnerNotificationFilter` (contrat `NotificationFilter`, inscrit par le module Partner) : un type « Partenaire » dans le filtre « Type » (ajout, modification, changement de statut) ;
  - pour ce type, un filtre « Statut du partenaire » (en validation, publié, refusé, archivé) : le statut que la notification annonce (`data.partner_status`) ;
  - chaque carte d'une notification de partenaire porte la pastille de ce statut ;
  - la recherche trouve aussi le nom du partenaire ;
  - la notification d'un ajout ou d'une modification nomme qui agit (`HasNotificationUser`) pour les autres comptes : son nom et sa photo sur la carte, le filtre « Membre ».
- **Motif** : la demande ; le même mécanisme que les demandes de logement.
- **Portée** : module Partner (filtre, fournisseur, notification, traductions) ; tests (dont la liste des filtres du module Offer).
- **Corollaires** : la pastille montre le statut annoncé par la notification, pas le statut actuel du partenaire (contrairement aux demandes, qui montrent le statut réel).
- **Tranché par** : humain (demande) ; session de nuit (filtrer plutôt que trier, statut annoncé plutôt que réel)
