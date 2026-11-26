Remplacée en partie par : 2026-11-26-titre-destinataire.md (le destinataire lit le titre enregistré)

# `2026-11-26` — Un seul titre de notification, calculé : `card_title` retiré

- **Type** : décision
- **Question posée** : « card_title est juste pour partner et booking : reprendre son contenu dans title et enlever card_title ».
- **Ce qui est retenu** :
  - l'API d'une notification ne sert plus qu'un `title` : celui que calcule le module de la notification, dans la langue du lecteur (une demande : « Demande de visite reçue », « Demande de séjour acceptée » ; un partenaire : « Partenaire modifié », « Partenaire accepté ») ; à défaut (un message), le titre enregistré à l'envoi (« Message ») ;
  - `card_title` est retiré de l'API et de l'app ; la méthode `cardTitle()` du contrat `NotificationFilter` devient `title()` (`NotificationFilters`, `OfferNotificationFilter`, `PartnerNotificationFilter`) ;
  - la carte et la barre du haut de la page lisent le même `title` ; la page d'une demande garde son titre propre (« Ma réservation », « Demande de séjour reçue »), posé par `BookingThreadDetailsService`.
- **Motif** : un seul titre, à jour, au lieu d'un titre figé à l'envoi et d'un second calculé.
- **Portée** : `NotificationResource`, contrat `NotificationFilter` et ses implémentations ; liste des notifications (app).
- **Corollaires** : une ancienne notification de demande ou de partenaire prend le titre du jour dans sa page aussi ; un module qui veut un titre à jour l'écrit dans `title()`.
- **Tranché par** : humain
