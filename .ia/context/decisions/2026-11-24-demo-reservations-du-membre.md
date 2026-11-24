# `2026-11-24` — Les réservations du membre de démonstration, une par type de location

- **Type** : décision
- **Question posée** : « ajouter des réservations de mon utilisateur connecté pour chaque type de location ».
- **Ce qui est retenu** :
  - Le compte connecté dans l'app est le membre de démonstration, propriétaire des logements de la démo. « Mes réservations » était vide.
  - `DemoSeeder::MEMBER_BOOKINGS` ajoute ses propres demandes chez l'autre propriétaire de la démo (`OTHER_OFFERS`), passées par `BookingService` :
    - **meublé** : un séjour au studio meublé à Bonanjo, accepté par le propriétaire ;
    - **location** : une visite de l'appartement deux chambres à Bonanjo, en attente ;
    - **colocation** : une visite de la chambre en colocation à Bonanjo, en attente.
  - Elles sont dans la démo, et non écrites en base : `booking:reset` les recrée.
- **Corollaire** : la démo passe à 19 demandes.
- **Tranché par** : humain (la demande) ; session (les statuts, dans la démo plutôt qu'en base)
