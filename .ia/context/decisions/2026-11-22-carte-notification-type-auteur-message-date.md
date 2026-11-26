Remplacée en partie par : 2026-11-26-carte-notification-statut.md (la pastille du statut revient sur la carte)

# Carte de notification : le type, qui envoie, le message, la date

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain, dans le chat (« enlever le titre composé, enlever le nom du logement, afficher le nom de la personne qui envoie si ce n'est pas nous, afficher le message, afficher la date » ; puis « afficher le type de la notification à la place du titre composé »)
- **Remplace** : 2026-11-22-carte-notification-type-titre-auteur.md, 2026-11-22-carte-notification-ville-sans-statut.md (pour la carte)

## Décision
La carte d'une notification montre :
1. le type de la notification, en gras (`title`) ;
2. le nom de qui envoie, sans lien, sauf si c'est le lecteur ;
3. son message, en italique gris, sur une ligne ;
4. la date, à droite.

Il n'y a plus de titre composé, de titre du logement, de statut ni de texte. Toucher la carte ouvre le détail.

## Motif
Une carte courte ; le logement et le statut se lisent sur le détail.

## Portée
`NotificationsScreen` (app). L'API sert toujours `card_title` et `card_subtitle`, que l'app n'affiche plus.
