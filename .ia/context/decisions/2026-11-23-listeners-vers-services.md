# `2026-11-23` — Listeners : le traitement dans un service

- **Type** : décision
- **Question posée** : « appliquer la skill create-listener ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - chaque listener reçoit son service par injection et son `handle()` ne fait que réunir les données de l'évènement, vérifier que l'appel a un sens et appeler le service ;
  - les cinq listeners de notification des demandes appellent `BookingNotifier` (une méthode par étape et destinataire) ;
  - `AddBookingToNotificationThread` appelle `BookingThreadDetails::forThread()` avec l'identifiant de la demande, la page et le lecteur ; il ignore les autres fils ;
  - tous restent synchrones (les notifications partent déjà en file par leurs canaux).
- **Motif** : la demande de l'humain, skill `bza-laravel:create-listener`.
- **Portée** : `Modules/Offer/app/Listeners`, `Modules/Offer/app/Services`.
- **Corollaires** : aucun changement de comportement ni d'API ; les tests d'écoute et d'appel sont dans `BookingListenersTest`.
- **Tranché par** : humain
