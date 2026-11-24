# `2026-11-24` — « Mes réservations » ouvre la page de la demande, même sans réponse

Complète : 2026-11-21-reservation-comme-notification.md

- **Type** : correctif
- **Constat** : depuis une seule notification par action (2026-11-24-notifications-fil-de-groupe, commit `6fbacf8`), celui qui fait une demande ne reçoit rien ; seul le propriétaire la reçoit. Or la carte de « Mes réservations » cherchait une notification reçue par le membre. Tant que le propriétaire n'avait pas répondu, elle ouvrait donc la fiche du logement au lieu de la page de la demande.
  - Sur les données de démo : deux visites en attente du membre sur trois réservations.
- **Ce qui est retenu** :
  - `Booking::latestVisibleNotification($userId)` donne la dernière notification de la demande que voit le compte, reçue par lui ou de sa propre action. C'est la règle de la liste « Notifications ».
  - « Mes réservations » et les demandes reçues s'en servent pour le lien de la carte.
  - Le compteur « N non lues » reste sur ce que le compte a reçu.
- **Preuve** :
  - sur les données de démo, les trois réservations du membre ouvrent `/notifications/…` ;
  - un test le vérifie : une demande en attente, reçue par le propriétaire, ouvre sa page chez le membre, sans compter comme non lue.
- **Tranché par** : humain (le constat) ; session (la correction)
