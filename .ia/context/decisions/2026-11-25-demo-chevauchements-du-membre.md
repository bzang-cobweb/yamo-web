# `2026-11-25` — Des chevauchements sur les demandes du membre de démonstration

- **Type** : décision
- **Question posée** : « rajouter les réservations faites par mon utilisateur avec chevauchement également pour les 3 types de location ».
- **Ce qui est retenu** :
  - Chaque demande en attente du membre, chez l'autre propriétaire, est chevauchée par deux demandes d'autres visiteurs (`DemoSeeder::MEMBER_OVERLAPS`) :
    - **meublé** : un second séjour du membre, en attente (jours 20 à 24), et deux séjours qui partagent des nuits avec lui ;
    - **location** : deux autres visites le même jour que la sienne (jour 5) ;
    - **colocation** : deux autres visites le même jour que la sienne (jour 7).
  - Le séjour accepté du membre reste tel quel.
  - Comme le reste de la démo, ces demandes sont rejouées par `booking:reset`.
- **Corollaire** : la démo passe de 19 à 26 demandes. Si l'autre propriétaire accepte l'une de ces demandes, celle du membre est refusée (règles 2026-11-22).
- **Tranché par** : humain (la demande) ; session (chevauchées par d'autres visiteurs, sur ses demandes en attente)
