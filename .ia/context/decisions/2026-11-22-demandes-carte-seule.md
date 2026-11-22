# « Demandes reçues » : ni bouton ni menu, la carte ouvre le détail

Remplace : 2026-11-22-demandes-sans-menu (boutons sur la carte)

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« enlever les bouton et laisser seulement le click sur la card qui dirige vers le détail
  de la demande »)

## Décision

Les cartes de « Demandes reçues » n'ont plus de bouton (Accepter, Refuser, Masquer / Afficher l'adresse) ni de
menu ⋮. Toucher la carte ouvre le détail de la demande ; le propriétaire répond depuis son bouton « Traiter la
demande », qui ouvre le calendrier du logement, dont le panneau du jour garde les boutons.

## Mécanique

`ReceivedBookingListing::item()` ne sert plus d'action ; `withAnswerActions()` les ajoute pour le panneau du jour
(`OfferCalendar`).

## Conséquence

Afficher ou masquer l'adresse d'une demande ne se fait plus que depuis le panneau du jour, pour les demandes qui y
figurent (en attente, acceptées) ; plus pour une demande refusée ou annulée.
