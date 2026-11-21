# Détail d'une réservation : lignes jusqu'au départ, puis « Voir plus » ; l'autre partie après les dates

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Remplace en partie** : 2026-11-20-lignes-demande.md (le nom de l'offre qui plie et déplie les lignes)
- **Complète** : 2026-11-21-detail-reservation-contact.md

## Décision

### Ordre des lignes
Les lignes de l'autre partie (Hôte ou Membre, Téléphone, E-mail) se placent juste après la
dernière date de la demande, avant la durée et les prix. Sans date, elles vont à la fin.
`AddBookingToNotificationThread::afterDates()` fait ce placement.

### Affichage dans l'app
- Le nom de l'offre est un titre simple, qui ne plie plus rien.
- Les lignes jusqu'à la dernière date (Arrivée, Départ, ou Visite, Seconde date) sont toujours
  visibles.
- Un lien « Voir plus » déplie la suite : l'autre partie, la durée, les prix et le total. Une
  fois déplié, le lien devient « Voir moins ».
- « Voir le logement » apparaît avec la suite dépliée, ou directement s'il n'y a pas de suite.
- Textes servis par l'API : `notifications.more` et `notifications.less`.

Une étape intermédiaire, « Détails » sous le titre pour plier les lignes, n'a pas été gardée.

## Motif
Demandes de l'humain du 2026-11-21 :
- « enlever le dropdown sur le titre et le mettre sur un texte "détails" juste en dessous du
  titre, déplacer hôte après checkin » ;
- puis « enlever le dropdown détails et mettre un lien voir plus après checkout ».
