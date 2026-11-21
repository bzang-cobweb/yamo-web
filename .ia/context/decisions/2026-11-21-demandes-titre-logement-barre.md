# « Demandes » d'une offre : le titre du logement dans la barre du haut

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« mettre le titre à la place de demandes »)

## Décision

Filtrée sur un logement, la barre du haut affiche le titre du logement (celui de sa carte) à la place de
« Demandes ». Sous les filtres ne reste que sa ligne du type (« Location · Appartement · Douala »). Sans
logement choisi : « Demandes » en haut, rien sous les filtres.

## Mécanique

`ReceivedBookingListing::titleFor()` ; `headingFor()` sert `{title: null, subtitle: <ligne>}` ; l'app
n'affiche le titre de l'intitulé que s'il y en a un.

## Corollaires

Remplace, pour le titre, 2026-11-21-demandes-titre-sous-filtres et 2026-11-21-demandes-d-une-offre.
