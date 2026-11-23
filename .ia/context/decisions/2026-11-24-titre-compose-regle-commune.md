# `2026-11-24` — Le titre composé d'une offre : une seule règle, `OfferCardTitle`

- **Type** : décision
- **Question posée** : « est-ce que la même règle existe pour le titre composé d'une offre ? type de location · type · ville ? » → « oui, aligne-les sur OfferCardTitle ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - la ligne « type de location · type · ville » est assemblée par le domaine (`RentalDomain::itemTypeLine()`) ; partout où l'on affiche le titre et la ligne d'une offre, on passe par `OfferCardTitle` (titre saisi, sinon titre construit ; ligne `null` si elle répète le titre) ;
  - le détail d'une demande (`BookingResource::composed_title`) et les formulaires « Modifier » et « Annuler la demande » (`BookingEditForm::title()` et `subtitle()`) passent désormais par `OfferCardTitle::of()` au lieu d'appeler `itemTypeLine()` directement ; sans titre saisi, la ligne du type devient le titre et n'est pas répétée.
- **Motif** : la demande de l'humain ; une règle, un domicile, comme `Partner::cardLine()`.
- **Portée** : `BookingResource`, `BookingEditForm`.
- **Corollaires** : `itemTitle()` (« Studio · meublé · Douala ») reste le repli de `OfferCardTitle` et le titre des cartes de demande (`BookingItem`).
- **Tranché par** : humain
