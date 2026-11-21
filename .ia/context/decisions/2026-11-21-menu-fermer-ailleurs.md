# Menu ⋮ des cartes : se ferme en touchant ailleurs

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« ferme dropdown 3 petits points quand je clique si ouvert »)

## Décision

Un menu ⋮ ouvert se ferme :
- en retouchant ⋮ ;
- en touchant une carte, le nom d'un membre ou un compteur : ce premier toucher ferme le menu sans rien
  ouvrir ;
- en faisant défiler la liste.

Toucher une entrée du menu l'exécute et ferme le menu, comme avant.

## Portée

App : `ProfileListScreen` (listes à menu : Mes logements, Mes réservations, Demandes) ; `List` sert
`onScrollBeginDrag`.

## Non couvert

Toucher la barre de recherche, les filtres ou la barre du haut ne ferme pas le menu.
