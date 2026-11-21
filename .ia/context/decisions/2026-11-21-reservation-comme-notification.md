# Une réservation s'ouvre comme la page d'une notification

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-reponse-dans-son-fil.md, 2026-11-20-suivi-conversation.md

## Décision

- Comme le propriétaire garde ses réponses, le membre garde dans son propre fil une copie déjà
  lue de sa demande et de son annulation (`NotifyGuestOfOwnRequest`, `BookingRequested` avec
  `own`, onglet seulement). Textes : « Vous avez demandé … », « Vous avez annulé votre demande … ».
- Toucher une carte de « Mes réservations » (ou « Voir » dans son menu) ouvre la page de la
  dernière notification du fil de la demande : la zone fixe (type de location, statut, lignes,
  « Voir le logement ») et la conversation. Sans notification dans le fil, la fiche du logement.
- L'app accepte un identifiant de notification (uuid) dans le lien d'une carte.
- Base locale : les copies manquantes des demandes existantes ont été ajoutées (15).

## Motif

Demande de l'humain du 2026-11-21 : « détail réservation comme détail notification ». Approche
choisie par l'humain : « Copie dans son fil » (plutôt qu'une page à part). Corollaire accepté :
la demande du membre apparaît aussi dans son onglet Notifications.
