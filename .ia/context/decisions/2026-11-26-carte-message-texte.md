# `2026-11-26` — La carte d'un message à l'éditeur montre le message

- **Type** : décision
- **Question posée** : « notification thread offer-message : c'est le message qu'on affiche dans les cards ».
- **Ce qui est retenu** :
  - le texte de la carte d'un message à l'éditeur (`summary`) est le message lui-même, tronqué comme toute carte (2026-11-26-cartes-notifications-tronquees), au lieu de « Au sujet de « titre » » ; le texte `offer::message.notify.summary` est retiré ;
  - l'offre dont on parle reste visible sur la page de la notification, dans son en-tête (2026-11-26-booking-summary) ; la carte n'a plus de lien vers elle ;
  - l'app n'affiche plus la ligne du message en italique quand elle répète le texte de la carte (règle générale, quel que soit le type) ;
  - la migration `2026_12_09_110000_message_card_text_is_the_message` donne le nouveau texte de carte aux messages déjà enregistrés.
- **Motif** : la carte d'un message dit ce qui a été écrit.
- **Portée** : `OfferMessageReceived`, la liste des notifications (app).
- **Tranché par** : humain (le message sur la carte) ; session (le message en italique masqué s'il répète le texte, la migration)
