# Mes réservations : « Annuler » dans le menu ⋮ ouvre le formulaire d'annulation

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« mes réservations dropdown card renommer annuler la demande en annuler et il ouvre le
  formulaire [d'annulation] ; il est visible si la demande peut encore être annulée »)

## Décision

Dans le menu ⋮ d'une carte de « Mes réservations », « Annuler la demande » devient « Annuler », en rouge, en
dernier. Il ouvre le formulaire d'annulation (message facultatif au propriétaire), le même que le bouton
« Annuler » de la page de la demande, au lieu d'annuler après une simple confirmation. Il n'apparaît que tant
que la demande peut être annulée.

## Mécanique

Une action de carte vers `BookingCancelForm` (style `destructive`), à la place du retrait (`delete`).

## Interprétation

« formulaire de modification de lien » lu comme le formulaire d'annulation de la page de la demande.
