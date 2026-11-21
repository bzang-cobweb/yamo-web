# « Demandes » d'une offre : le nom du membre en lien vers sa page

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« mettre le lien sur le nom du membre »)

## Décision

Sur une carte de « Demandes » d'une offre, le nom du membre s'affiche en lien : le toucher ouvre sa page
(`/members/<id>`). Le reste de la carte ouvre toujours la page de la demande.

## Mécanique

`ListItem::titleLink()` sert `title_link` (absent sans lien) ; l'app affiche alors le titre en lien vers cet
écran, sans changer le lien de la carte.

## Portée

`ListItem`, `OfferRequestItem` ; app : `ProfileListScreen`, type `ListItem`.
