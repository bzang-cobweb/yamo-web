# « Demandes reçues » : la date à droite du nom, en chiffres quand elle est entière

Remplace : 2026-11-22-demandes-date-derniere-notification.md (pour la place et le format)

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« cette date doit être en chiffre quand on l'affiche en entier / mettre cette date
  alignée à droite du nom du membre »)

## Décision

La date de la dernière notification d'une demande s'affiche sur la ligne du nom du membre, alignée à droite. En
temps relatif jusqu'à une semaine (« il y a 2 h », « hier »…), puis en chiffres (« 03/01/2026 »).

## Mécanique

`relativeTime(…, numeric)` ; les cartes de notification gardent la date en lettres.
