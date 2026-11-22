# « Demandes reçues » : un point devant le nom tant qu'il reste du non lu

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« point 3 », parmi trois propositions pour remplacer la pastille des non lues)

## Décision

Tant qu'une demande a des notifications non lues pour le propriétaire, un petit point coloré (couleur d'accent)
précède le nom du membre sur sa carte, comme dans une messagerie ; sans chiffre. Lu par l'accessibilité : « Non
lue ».

## Mécanique

`ListItem::unread()` sert `unread: true` (absent sinon) ; l'app dessine le point devant le titre.

## Corollaires

Complète 2026-11-22-demandes-sans-pastille-non-lues.
