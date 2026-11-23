# Page d'une demande : le titre composé et les dates avant l'adresse, dans les lignes

Remplace : 2026-11-22-detail-titre-compose-et-dates.md (pour le titre composé et les dates ; « Ma réservation » / « Demande reçue » restent dans la barre du haut)

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« déplacer le titre composé dans la section avant adresse / enlever la date de visite de la demande précédente / déplacer checkin et checkout avant adresse »)

## Décision

- Le titre composé et les dates ne sont plus des lignes à part en tête de la page.
- Les lignes de la demande commencent par :
  1. « Logement » : le titre composé sans ville (« Location meublée · Studio ») ;
  2. « Disponible dès le », si renseigné (une visite) ;
  3. les dates : « Arrivée » et « Départ » pour un séjour, « Visite » pour une visite ;
  4. « Adresse » ;
  5. le contact de l'autre partie, juste après l'adresse, ou, sans adresse, après les dates ;
  6. puis personnes, durée, prix, total, comme avant.
- Toujours deux lignes visibles avant « Voir plus » : « Logement » et la première date.

## Portée

`BookingResource::lines` (ligne `composed`, nouvelle, texte `lines.composed`) et `AddBookingToNotificationThread` (plus de `composed_title` ni de `period`, contact placé après les dates sans adresse). Mobile : `NotificationDetailScreen` sans les deux lignes d'en-tête.
