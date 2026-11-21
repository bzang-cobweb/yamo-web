# « Mes réservations » : les cartes de « Mes logements », pour celui qui réserve

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Remplace en partie** : 2026-11-21-reservations-statut-coeur.md (le cœur de favori)

## Décision

Les cartes de « Mes réservations » reprennent celles de « Mes logements » :

- le menu trois points en haut à droite de la photo (`actionsMenu()`), avec Voir, Notifications,
  Annuler la demande ; le statut en haut à gauche ;
- plus de cœur de favori ;
- « Notifications » ouvre `/notifications/filtered` avec un retour, filtrées sur les demandes de
  ce logement (type demande, logement) ;
- un bouton « x notifications non lues » (les notifications non lues de cette demande), avec les
  mêmes filtres plus « Non lues : Oui » ; à 0, pas de bouton.

L'app n'a pas changé : le menu, les compteurs et les actions filtrées sont génériques.

## Motif

Remarque de l'humain du 2026-11-21 : « la page mes réservations c'est la page mes logements mais
pour l'utilisateur ». À reprendre, choisi par l'humain : menu trois points, bouton non lues,
entrée Notifications (pas le cœur).
