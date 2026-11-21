Remplacée par : 2026-11-21-pastille-sur-la-photo.md (pour la place de la pastille)

# Mes logements : pastille orange « N demandes en attente » sous le titre

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« Oui, fais la pastille demandes en attente »), sur proposition

## Décision

Sur une carte de « Mes logements », le compteur des demandes en attente devient une pastille orange
(la couleur de « En attente »), texte blanc, placée juste sous le titre. La toucher ouvre, comme
avant, les notifications des demandes en attente de ce logement. Sans demande en attente, pas de
pastille. Le compteur des notifications non lues reste en bas de la carte, en gris.

## Mécanique

`ListItem::counter()` prend un ton facultatif (`warning`, `success`, `danger`, `neutral`), servi en
`tone` ; l'app affiche sous le titre, dans cette couleur, tout compteur qui en a un, et les autres
en bas de la carte.

## Motif

Repérer d'un coup d'œil, dans la liste, les logements qui attendent une réponse.

## Portée

`ListItem` (Form), `OfferListing` ; app : `ProfileListScreen`, type `ListItemCounter`.

## Corollaires

Complète 2026-11-19-contrat-reservations-logements-profil et 2026-11-21-compteur-non-lues.
