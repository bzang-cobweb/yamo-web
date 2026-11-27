# `2026-11-27` — Pastilles d'une carte à image latérale : sous le contenu de droite

- **Type** : décision
- **Question posée** : « mauvais affichage d'une card si je ne suis pas admin du partner », puis « mettre les tags vues et membres en dessous du contenu de droite ».
- **Constat** : une carte à image latérale sans « Modifier » ni « Retirer » (un compte non administrateur de « Mes partenaires ») restait sur une ligne ; ses pastilles (« 21 vues », « 3 membres ») se plaçaient à droite du texte et l'écrasaient.
- **Ce qui est retenu** : à côté d'une image, les pastilles pour information sont dans la colonne de droite, sous le contenu (titre, sous-titre…), alignées à gauche comme lui. Sans image à côté, elles restent en bas de la carte, à droite, et la carte passe en colonne pour les y mettre, jamais à côté du texte.
- **Portée** : mobile, `ListItemCard` (`countersRow`, `isStacked()`) : toutes les cartes à image latérale qui ont ces pastilles.
- **Tranché par** : humain
