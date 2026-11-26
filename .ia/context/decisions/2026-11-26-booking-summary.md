# `2026-11-26` — En-tête générique de la page d'une notification ; `BookingSummary` pour ce qui est propre à une demande

- **Type** : décision
- **Questions posées** : « sortir le bloc demande dans un composant BookingSummary, mais est-ce que le nom du logement et le titre composé ne peuvent pas être utilisés comme titre et sous-titre de la notification, et BookingSummary ne fait que ce qui est propre au booking ? » ; puis « utiliser heading pour le thread offer-message, exactement comme le thread booking ».
- **Ce qui est retenu** :
  - la page d'une notification (`GET /api/v1/notifications/{id}`) sert `heading` : `{title, subtitle, link}` ou `null`. `NotificationResource` le met à `null` ; le module du fil le remplit. Pour une demande, `BookingThreadDetailsService` y met le nom du logement, son titre composé et le lien vers sa fiche ;
  - l'app affiche `heading` pour toute notification, sous la pastille : le titre en gras, centré, en lien s'il a un `link` ; le sous-titre en gris dessous. Sans `heading`, le texte de la notification (`summary`, à défaut `body`) à la place ;
  - `BookingSummary` (`mobile/src/features/offer/components/`) n'affiche que les lignes de la demande et « Voir plus » / « Voir moins » ; l'écran de détail l'affiche quand `booking` est servi ;
  - le fil d'une discussion avec l'éditeur (`offer-message:{offre}:{membre}`) a le même en-tête : le listener `AddOfferToMessageThread` le remplit ; demande et discussion le construisent par `OfferHeading::of()` (titre saisi, titre composé d'`OfferCardTitle`, lien `/offers/{id}`), une offre supprimée comprise ;
  - `booking.offer.title` et `booking.composed_title` restent servis (une app déjà installée les lit encore), l'app ne s'en sert plus.
- **Motif** : l'écran de détail ne connaît plus la forme d'une demande pour son en-tête ; un autre type de fil peut donner le sien sans toucher à l'écran.
- **Portée** : page d'une notification (serveur et app).
- **Corollaires** : un module qui veut un en-tête le met dans `heading` depuis son listener de `NotificationThreadSerializing` ; un en-tête masque le texte de la notification sur la page (pas sur la carte de la liste).
- **Tranché par** : humain (titre et sous-titre de la notification, `BookingSummary` limité à la demande) ; session (le champ `heading` et son lien, le texte masqué quand il y a un en-tête, les champs de `booking` gardés)
