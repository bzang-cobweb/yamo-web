# Mes logements : la pastille des demandes en attente n'est pas un lien

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« enlever le lien sur la pastille. la pastille est là juste à titre d'information »)

## Décision

La pastille orange « N demandes en attente » (sur la photo, à la place du statut ; sans photo, sous
le titre) informe seulement : elle ne se touche pas, comme les pastilles de statut. Pas de flèche
non plus. Les demandes se retrouvent par l'action « Notifications » du menu de la carte, ou par
« Demandes reçues ».

## Mécanique

Dans l'app, un compteur qui a un ton s'affiche en pastille de statut, sans lien ; les compteurs sans
ton (« 1 notification non lue ») restent des liens, en bas de la carte. Le serveur continue de
servir les filtres du compteur, inutilisés pour une pastille.

## Portée

`ProfileListScreen` ; `ItemPicture` revient à une pastille non touchable.

## Corollaires

Complète 2026-11-21-pastille-sur-la-photo.
