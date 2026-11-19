# `2026-11-19` — Réservations : événements et notifications Laravel mises en file

- **Type** : décision
- **Question posée** : « suivre la doc officielle (notifications Laravel 13) pour la mise en place des notifications avec queue sur les channels sélectionnés » ; puis l'événement de la demande et la gestion des contenus SMS et e-mail.
- **Réponse** : « ok rename OfferBooked to BookingRequested », « yes do it ».
- **Ce qui est retenu** :
  - chaque étape est un événement : `BookingRequested`, `BookingAnswered`, `BookingCancelled` ; un écouteur par événement prévient l'autre partie ;
  - une notification par étape (`BookingRequestReceived`, `BookingRequestAccepted`, `BookingRequestRefused`, `BookingRequestCancelled`), `ShouldQueue`, `afterCommit()` (la file `database` n'attend pas les transactions) ;
  - canaux : `User::notificationChannels()` — e-mail et SMS ouverts par l'application (`auth.channels`) et vérifiés pour le compte ; canal SMS maison `SmsChannel` (module Auth) sur `SmsSender` ;
  - contenus : `toMail()` (`MailMessage`) et `toSms()`, textes dans `offer::booking.notify.*` (fr, en, de), langue du destinataire par `HasLocalePreference`.
- **Motif** : la demande de l'humain ; la documentation officielle.
- **Portée** : module Offer (événements, écouteurs, notifications, `BookingService`), module Auth (`SmsChannel`, `User`).
- **Corollaires** : une file doit tourner (`queue:work`) pour que les notifications partent ; `BookingNotifier` et `BookingMail` sont retirés. Pastilles sur les listes de réservations : abandonnées au profit d'un module de notifications dans la barre du haut.
- **Tranché par** : humain
