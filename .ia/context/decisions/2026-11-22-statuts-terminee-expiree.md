Remplacée par : 2026-11-22-statut-termine-enregistre.md

# Demandes : « Terminée » et « Expirée », d'après les dates

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« faire les étapes 1 et 2 », après la proposition sur les dates passées)

## Décision

Deux statuts affichés, calculés d'après les dates. Ils ne sont jamais enregistrés, et le statut en base ne change pas.

- **Terminée** (pastille grise) : la demande est acceptée et son dernier jour est passé (le départ d'un séjour, la dernière date proposée d'une visite).
- **Expirée** (pastille grise) : la demande est en attente et sa date est passée. Pour un séjour, c'est l'arrivée ; pour une visite, la dernière date proposée. Une arrivée aujourd'hui n'est pas encore expirée. Une demande expirée :
  - ne peut plus être acceptée, refusée, modifiée ni annulée (403 « Cette demande a expiré : sa date est passée. ») ; les boutons disparaissent de la page de la demande et du calendrier ;
  - ne compte plus dans « en attente » : compteur de « Mes logements », filtre « En attente » des listes et des notifications, regroupement « pending » ; elle passe dans « past » ;
  - ne bloque plus une nouvelle demande de visite (« une seule demande en attente »), ni les chevauchements à refuser à l'acceptation, ni la couleur « en attente » du calendrier.

Personne n'est prévenu à l'expiration : il n'y a pas de tâche planifiée.

## Portée

- `Booking` : `isExpired`, `isCompleted`, `lastDay`, `statusTag`, scopes `expired` et `awaitingAnswer` ; `isAwaitingAnswer` et `isCancellable` excluent les expirées.
- Pastilles : `BookingItem`, `OfferRequestItem`, `BookingListing`, `OfferNotificationFilter::tag` et le calendrier lisent `statusTag()`.
- Textes : `statuses.completed` et `statuses.expired`, et le message `expired` (fr, en, de).
