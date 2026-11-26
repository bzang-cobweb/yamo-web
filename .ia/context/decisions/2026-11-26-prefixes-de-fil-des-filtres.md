# `2026-11-26` — Le préfixe d'un fil de notifications est la clé de son type dans le filtre

- **Type** : décision
- **Questions posées** : « utiliser les constantes définies dans chaque xxxNotificationFilter à la place de THREAD_PREFIX, comme ça c'est fait à un seul endroit. `OfferNotificationFilter::MESSAGE = 'offer_message'` » ; puis « faire une méthode statique pour chaque xxxNotificationFilter, exemple : `OfferNotificationFilter::bookingThreadPrefix()` ».
- **Ce qui est retenu** :
  - la clé d'un type dans le filtre « Type » des notifications préfixe aussi le fil : `{clé}:…` ;
    - demande : `OfferNotificationFilter::BOOKING` → `booking:{demande}` (inchangé) ;
    - message à l'éditeur : `OfferNotificationFilter::MESSAGE = 'offer_message'` → `offer_message:{offre}:{membre}` (au lieu de `offer-message:`) ;
    - partenaire : `PartnerNotificationFilter::PARTNER` → `partner:group:{partenaire}` (inchangé, par `Partner::groupThreadPrefix()`) ;
  - chaque filtre donne son préfixe par une méthode statique : `OfferNotificationFilter::bookingThreadPrefix()` (`booking:`), `OfferNotificationFilter::messageThreadPrefix()` (`offer_message:`), `PartnerNotificationFilter::partnerThreadPrefix()` (`partner:group:`) ; personne d'autre ne compose le préfixe ;
  - `Booking::THREAD_PREFIX` et `OfferMessageReceived::THREAD_PREFIX` sont retirés ; `Booking::thread()`, `OfferMessageReceived::thread()` et les listeners de `NotificationThreadSerializing` lisent la constante du filtre ;
  - la migration `2026_12_09_100000_rename_offer_message_threads` renomme les fils `offer-message:` déjà enregistrés (retour arrière renseigné).
- **Motif** : une seule déclaration par type, pour le filtre comme pour le fil.
- **Portée** : modules Offer et Partner.
- **Corollaires** : un nouveau type de notification avec un fil déclare sa clé dans son `NotificationFilter` et en préfixe son fil.
- **Tranché par** : humain (les constantes des filtres, `offer_message`) ; session (le préfixe `{clé}:`, `partner:group:` gardé, la migration)
