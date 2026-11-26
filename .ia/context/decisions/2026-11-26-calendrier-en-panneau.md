# `2026-11-26` — Le calendrier d'un champ date s'ouvre dans un panneau

- **Type** : décision
- **Question posée** : « le champ pour les date s'ouvre pousse le reste du formulaire ».
- **Ce qui est retenu** : toucher un champ date ouvre son calendrier dans un `BottomSheet` à la taille du calendrier (`fitContent`), titré du libellé du champ, au lieu de le déplier sous le champ. Vaut pour la grille de mois (`CalendarDateField` : arrivée, départ, visite) et pour le sélecteur natif iOS (`DateField` : date de naissance) ; Android garde sa boîte de dialogue. Choisir une date ferme le panneau ; la croix ou le fond aussi.
- **Motif** : le formulaire ne bouge plus ; comme les listes déroulantes, qui s'ouvrent déjà par-dessus.
- **Tranché par** : humain
