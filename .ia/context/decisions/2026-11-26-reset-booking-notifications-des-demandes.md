# `2026-11-26` — `booking:reset` n'efface que les notifications des demandes

- **Type** : décision
- **Question posée** : « pourquoi les types partenaire et message n'apparaissent pas dans les filtres des notifications ? » → « corriger les deux ». Cause : `booking:reset` effaçait toutes les notifications, celles des partenaires comprises, et ne recréait que celles des demandes.
- **Ce qui est retenu** : `booking:reset` efface les demandes et leurs notifications seulement : les étapes (`BookingStepEnum::notifications()`) et les invitations à donner un avis (`BookingReviewInvitation`). Les notifications des partenaires, des messages à l'éditeur et de tout autre type restent. La confirmation et le résumé disent « … demandes et leurs … notifications ».
- **Motif** : remettre la démo des demandes sans perdre le reste de l'onglet Notifications.
- **Portée** : la commande `booking:reset` (développement seulement).
- **Corollaires** : un nouveau type de notification d'une demande doit être ajouté à la liste de `ResetBookingsCommand::bookingNotifications()`.
- **Tranché par** : humain (corriger) ; session (les types effacés)
