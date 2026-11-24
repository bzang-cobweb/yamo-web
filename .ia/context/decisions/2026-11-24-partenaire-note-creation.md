# `2026-11-24` — La note du formulaire partenaire, à la création seulement

- **Type** : décision
- **Question posée** : « partenaire formulaire : mettre la note seulement lors de la création, et modifier la note en indiquant que nous allons valider les données (et confirmer le paiement avant) de l'afficher. La partie entre parenthèses, c'est pour la note si le paiement est activé. »
- **Ce qui est retenu** :
  - La note du formulaire de l'app (`PartnerForm::notice()`) ne s'affiche qu'à l'ajout d'un partenaire, pas à sa modification.
  - Paiement des partenaires non appliqué : « Nous allons d'abord valider vos données avant d'afficher ce partenaire sur l'application. »
  - Paiement appliqué (`Setting::partnerPaymentRequired()`) : « Nous allons d'abord valider vos données et confirmer le paiement avant d'afficher ce partenaire sur l'application. »
  - En anglais et en allemand : les mêmes textes (`review_notice`, `review_notice_paid`).
- **Tranché par** : humain
