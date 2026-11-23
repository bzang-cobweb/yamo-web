# Page d'une demande : les libellés suivis de deux-points, en haut de leur ligne

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« mettre : devant les labels de gauche », « label de gauche pas centré verticalement si contenu de droite sur plusieurs lignes »)

## Décision

- Chaque libellé de gauche des lignes d'une demande se termine par deux-points : « Arrivée : ». En français, une espace insécable les précède. En anglais et en allemand, il n'y a pas d'espace (« Arrival: »).
- Le libellé est en haut de sa ligne, aligné avec la première ligne de la valeur, même quand celle-ci en prend plusieurs (une adresse longue). Il n'est plus centré verticalement.

## Portée

Mobile : `NotificationDetailScreen` (`renderLine`, style `lineRow`).
