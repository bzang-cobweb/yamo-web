# « Mes logements » : les actions des cartes dans un menu déroulant

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-19-nuit-voir-editer-retirer.md, 2026-11-21-editer-avant-retirer.md

## Décision

Une liste peut regrouper les actions de ses cartes dans un menu (`Listing::actionsMenu()`,
servi en `menu: true`) ; `OfferListing` (« Mes logements ») le fait. Chaque carte n'a alors
qu'un bouton « Actions » à droite ; ouvert, il liste dessous, dans l'ordre : Voir, Éditer, les
boutons de la carte (Calendrier), Retirer (en rouge). Choisir une entrée referme le menu. Le
compteur de demandes en attente reste sur la carte. Les autres listes ne changent pas.

## Motif

Demande de l'humain du 2026-11-21 : « regrouper toutes les actions des cards dans mes
logements dans un dropdown ». Menu dans la carte (qui s'ouvre dessous) plutôt qu'une feuille
d'actions du système : décidé seul.

## Corollaires

- Texte « Actions » servi par l'API (`list.actions`, fr/en/de).
- En passant : un test du calendrier dépendait de la date du jour (+41 jours tombait en janvier
  le 21 novembre) ; sa date est désormais fixe.
