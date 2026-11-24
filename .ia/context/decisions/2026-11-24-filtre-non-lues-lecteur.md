# `2026-11-24` — Le filtre « Non lues » suit ce que le lecteur a reçu

- **Type** : correctif
- **Constat** : le filtre « Non lues » ne fonctionnait plus. Depuis le commit `6fbacf8` (2026-11-24-notifications-fil-de-groupe), une carte peut être une notification que le lecteur n'a pas reçue : sa propre action, ou une notification du fil d'un partenaire. Le filtre jugeait la carte sur l'état de lecture de son destinataire, c'est-à-dire de l'autre compte.
  - Données de démo :
    - compte 36 : 2 cartes « non lues » au lieu de 0 ;
    - compte 35 : 8 au lieu de 11.
  - La pastille et l'état des cartes, eux, étaient justes : ils passent par `unreadThreads()`, qui ne regarde que ce que le lecteur a reçu.
- **Ce qui est retenu** :
  - `NotificationThreads::unreadCard($user)` donne la règle en condition de requête : une carte est non lue si le lecteur a, dans son fil, une notification reçue et pas lue, ou, sans fil, si la notification lui est adressée et pas lue.
  - Le filtre « Non lues » de `NotificationFilters` s'en sert. C'est la même règle que la pastille et l'état d'une carte.
- **Preuve** : sur les données de démo, le filtre donne pour chaque compte le même nombre que les cartes non lues. Deux tests le vérifient : une demande et sa réponse, et le fil d'un partenaire.
- **Tranché par** : humain (« oui corrige »)
