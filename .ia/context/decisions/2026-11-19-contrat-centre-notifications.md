# `2026-11-19` — Contrat « Module Notification », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-centre-notifications.md` ; où mettre le lien ; pastille à zéro ; conservation ; notifications poussées.
- **Réponse** : « show badge even if 0 », conservation « in notification config file and in .env », « include phone push notifications but not now, I will tell you when », « bottom tab between home and profile », puis « Approved ».
- **Ce qui est retenu** :
  - un onglet « Notifications » dans le menu du bas, entre Accueil et Profil, pour un membre connecté ; pastille du nombre de non lues, toujours affichée (« 0 » compris) ;
  - l'écran : liste la plus récente en tête ; toucher marque lue et ouvre l'écran désigné ; « Tout marquer comme lu » ;
  - module Laravel `Notification` (canal `database`, API) ; les quatre notifications de réservation y apparaissent toujours ;
  - effacement quotidien au-delà de `notification.retention_days` (`NOTIFICATION_RETENTION_DAYS`, 90 par défaut, dans `.env` et `.env.example`) ;
  - notifications poussées : plus tard, sur signal de l'humain (dépendance à autoriser alors).
- **Motif** : la demande de l'humain.
- **Portée** : nouveau module `Notification`, module Offer (notifications de réservation), app (onglet, écran).
- **Corollaires** : plus de pastille sur « Mes réservations » ni « Réservations reçues ».
- **Tranché par** : humain
