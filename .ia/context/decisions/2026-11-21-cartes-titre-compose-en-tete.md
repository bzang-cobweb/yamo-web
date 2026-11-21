# Cartes : la ligne du type en titre, le titre saisi dessous

Remplace : 2026-11-21-cartes-titre-saisi-ligne-type.md (pour l'ordre)

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« sur les cards de l'accueil, de mes réservations et de mes logements, inverser le
  titre composé et le titre » ; « le faire aussi sur les favoris »)

## Décision

Sur les cartes de l'accueil, de « Mes logements », de « Mes réservations » et des favoris : en titre, la
ligne du type (« Location meublée · Appartement · Yaoundé ») ; dessous, le titre saisi. Sans ligne du type, le
titre saisi seul. Dans « Mes réservations », le type de demande et les dates suivent sous le titre saisi.

## Mécanique

`OfferCardTitle::composedFirst()` ; `OfferCardTitle::of()` reste l'ordre « titre saisi d'abord » pour le
titre de la barre de « Demandes » et les options du filtre « Logement ».
