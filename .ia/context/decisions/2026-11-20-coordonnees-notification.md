Remplacée par : 2026-11-20-sans-coordonnees.md

# Coordonnées de qui agit, sur la page d'une notification

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« afficher email et téléphone dans détail de notification et déplacer date après le message aligné à droite »)
- **Complète** : 2026-11-19-page-membre.md (qui réservait les coordonnées à une nouvelle décision)

## Décision

- **Où** : sur la page d'une notification (`GET /v1/notifications/{id}`), sous le nom de qui agit :
  - son e-mail ;
  - son téléphone, au format international.

  Les deux s'ouvrent dans l'app de messagerie ou de téléphone.
- **Qui les voit** : seul le destinataire de la notification, puisque la page ne s'ouvre que pour lui.
- **Où ils restent cachés** : la liste des notifications et la page publique d'un membre.
- **La date** : la date complète passe après le message, alignée à droite.

## Motif

Le propriétaire qui reçoit une demande peut joindre le membre directement.

## Corollaires

- Montrer les coordonnées ailleurs (la liste, la page d'un membre) demanderait une nouvelle décision.
