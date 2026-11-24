# `2026-11-24` — Des demandes qui se chevauchent, pour chaque type de location, dans la démo

- **Type** : décision
- **Question posée** : « ajouter à ma base locale des chevauchements pour chaque type de location ».
- **Ce qui est retenu** :
  - Les chevauchements sont ajoutés aux demandes de démonstration (`DemoSeeder::BOOKINGS`), rejouées par `booking:reset`, plutôt qu'écrits en base : un prochain « reset booking » ne les efface pas.
  - **Meublé** (séjours) : des dates communes. C'était déjà le cas : trois demandes en attente sur le studio meublé à Akwa (2026-11-21-demo-demandes-chevauchement).
  - **Location** (visites) : trois visites en attente le même jour sur l'appartement 3 pièces à Bonapriso (`visit_pending`, `visit_overlap`, `visit_overlap_late`).
  - **Colocation** (visites) : trois visites en attente le même jour sur la colocation dans une grande maison (`shared_visit_*`).
  - Pour une visite, il n'y a qu'une date : le chevauchement qui compte est le même jour, puisque accepter une visite touche les autres de ce jour (2026-11-22-accepter-autres-visites).
- **Corollaire** : la démo passe de 11 à 16 demandes.
- **Tranché par** : humain (la demande) ; session (le même jour pour une visite, dans la démo plutôt qu'en base)
