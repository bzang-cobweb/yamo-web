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
- Les lignes jusqu'à la dernière date (Arrivée, Départ, ou Visite, Seconde date) sont toujours
  visibles.
- Le nom de l'offre ouvre la fiche du logement. Le lien « Voir le logement » est retiré.
- « Voir plus », avec une icône +, déplie la suite : l'autre partie, la durée, les prix et le
  total. Une fois déplié, il devient « Voir moins », avec une icône −.
- Ce lien reste toujours à la fin des lignes, même déplié, aligné à droite.
- Textes servis par l'API : `notifications.more` et `notifications.less`.

Une étape intermédiaire, « Détails » sous le titre pour plier les lignes, n'a pas été gardée.

## Motif
Demandes de l'humain du 2026-11-21 :
- « enlever le dropdown sur le titre et le mettre sur un texte "détails" juste en dessous du
  titre, déplacer hôte après checkin » ;
- puis « enlever le dropdown détails et mettre un lien voir plus après checkout » ;
- puis « le lien voir plus/moins doit être à la fin même déplié et aligné à droite » ;
- puis « enlever le lien voir le logement et rendre le titre cliquable » ;
- puis « mettre icone + et - sur voir plus/moins ».
