# Mes réservations : le sous-titre de l'accueil, puis le type de demande et les dates sur leur ligne

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« mettre le sous-titre des cards comme l'accueil » → « Sur sa ligne, dessous »)

## Décision

Sur les cartes de « Mes réservations », le sous-titre est celui de l'accueil : le titre saisi du logement, une
ligne, petit et gris. Le type de demande et les dates (« Séjour · du 2 déc. 2026 au 6 déc. 2026 ») passent
sur leur propre ligne, dessous, en petit, dans la couleur du texte.

## Mécanique

`ListItem::detail()` sert `detail` (absent sans ligne) ; l'app l'affiche sous le sous-titre.

## Décidé seul (à confirmer)

Le style de la ligne : petit, couleur du texte (le sous-titre est petit et gris).
