# `2026-11-24` — Chaque action est confirmée à son auteur par e-mail et SMS

- **Type** : décision de conception
- **Question posée** : « est-ce qu'avec cette solution on peut aussi envoyer le mail et/ou le sms au membre qui a fait l'action ? » ; réponse : « oui, e-mail et SMS seulement pour toutes les actions à l'émetteur (user_id) ».
- **Ce qui est retenu** :
  - Chaque action d'une partie lui est confirmée par e-mail et SMS, selon les canaux qu'elle peut recevoir, sans notification dans l'onglet. Il n'y a donc pas de ligne en base, et le fil garde une seule ligne par action (2026-11-24-notifications-fil-de-groupe).
  - Réservations, avec le listener `ConfirmBookingActionToActor` et `BookingNotifierService::confirmToActor()` :
    - le membre est confirmé pour une demande, une modification ou une annulation ;
    - le propriétaire pour une réponse.
    - La notification part avec `inTab: false`, et son e-mail et son SMS prennent les textes de l'auteur : `own_subject`, `own` et `own_sms` (fr, en, de).
  - Partenaires : le membre qui ajoute ou modifie depuis l'app reçoit sa confirmation (`PartnerNotification`, `inTab: false`).
  - Le back-office et le système ne reçoivent jamais de confirmation.
- **Corollaire** : une action déclenche un SMS chez son auteur s'il a un numéro vérifié. C'est un coût d'envoi en plus.
- **Tranché par** : humain
