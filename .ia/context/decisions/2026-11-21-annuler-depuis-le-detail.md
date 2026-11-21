# Annuler sa demande depuis sa page, par un formulaire

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : « ajouter le bouton annuler sur le détail réservation. Ouvre formulaire avec les mêmes champs que celui d'édition mais seul le champ message est activé et label bouton Confirmer »

## Décision
- Sur la page d'une demande, pour le membre qui l'a faite et tant qu'il peut l'annuler (en
  attente, ou acceptée et pas encore commencée), un second bouton « Annuler », bordé de rouge, à
  gauche du bouton principal (« Modifier » ou « Mes réservations »). Servi par l'API :
  `secondary_link`, `secondary_link_label`.
- Il ouvre le formulaire `offer.booking.cancel` : les champs de « Modifier la demande », remplis
  et en lecture seule, et le message, vide, « Message au propriétaire », facultatif. Titre
  « Annuler la demande », bouton « Confirmer ».
- Le message est gardé dans `bookings.cancel_message` et joint à l'étape « Demande annulée »
  (suivi, e-mail, SMS).

## Choix faits seul
- Le message part vide (c'est le motif de l'annulation, pas le message de la demande).
- L'annulation depuis le menu de la carte de « Mes réservations » reste une confirmation sans
  message.

## Portée
`POST /api/v1/bookings/{booking}/cancel` accepte un `message` facultatif, validé par le
formulaire ; les autres champs envoyés sont ignorés.
