Remplacée par : 2026-11-21-demandes-d-une-offre.md (cartes et filtres)

# « Demandes » d'un logement : la liste des demandes reçues, comme « Mes réservations »

Remplace : 2026-11-21-lien-notifications.md (pour l'action « Demandes » des cartes de « Mes logements »)

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« le lien demandes redirige vers la même liste que mes réservations avec comme
  titre demandes avec le logement sélectionné dans le filtre »)

## Décision

- L'action « Demandes » du menu d'une carte de « Mes logements » ouvre la liste des demandes reçues
  (`offer.bookings.received`), ce logement déjà choisi dans le filtre « Logement », au lieu des
  notifications filtrées.
- Cette liste est titrée « Demandes », quel que soit le filtre.
- Elle a les mêmes filtres que « Mes réservations » (statut réel, période, ville, logement avec
  recherche, types de l'offre), sur les logements du propriétaire.
- Ses cartes sont celles de « Mes réservations » : titre saisi, ligne du type, puis le membre, le type
  de demande et les dates ; la note ; le prix et son unité en gras ; le statut sur la photo ; les
  actions (Accepter, Refuser, Masquer / Afficher l'adresse) dans le menu trois points. La carte ouvre
  la page de la demande (sa dernière notification), à défaut la fiche du logement. Le message du membre
  n'est plus cité sur la carte : il se lit sur cette page.

## Mécanique

Les filtres sont définis une fois, dans le trait `Lists/Concerns/FiltersBookings`, que les deux listes
utilisent ; chacune dit seulement quels logements proposer (`bookedOffers()`). Dans l'app, une action qui
sert une liste et des filtres sans lien choisit ces filtres puis ouvre `/profile/lists/<liste>`.

## Portée

`ReceivedBookingListing`, `BookingListing`, `OfferListing` ; app : `useItemActions`.

## Corollaires

Le titre « Demandes · <logement> » disparaît (clé `requests_title` retirée). Remplace, pour la citation
du message sur la carte, 2026-11-19-message-sur-les-demandes-recues. Les compteurs « demandes en
attente » et « notifications non lues » ne changent pas.
