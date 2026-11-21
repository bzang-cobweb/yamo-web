# « Mes réservations » : filtre Statut réel ; l'ancien devient « Période »

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : « mes réservations filtres ajouter filtre Statut sélect qui se base réellement sur les statuts d'un booking (en attente, annulée, acceptée et rejetée). le filtre statut actuel le renommer proposer un nom et le transformer en select »

## Décision
- Nouveau filtre « Statut » (`status`), liste déroulante des statuts réels d'une demande : En
  attente, Acceptée, Refusée, Annulée (libellés de `BookingStatusEnum`, « Refusée » plutôt que
  « Rejetée » pour rester cohérent avec la pastille). Vide : tous.
- L'ancien filtre (à venir, en attente, passées) est renommé « Période » (`period`, nom proposé
  par l'IA) et passe en liste déroulante, comme dans « Mes logements » (vide : toutes).
- Ordre : Statut, Période, Ville, Type. Les deux filtres se combinent.

## Portée
« Mes réservations » seulement ; « Réservations reçues » garde son filtre à segments.
