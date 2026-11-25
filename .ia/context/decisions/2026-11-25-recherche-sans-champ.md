# `2026-11-25` — La page de recherche sans champ de recherche

- **Type** : décision
- **Question posée** : « enlever le champ de recherche sur la recherche », après « le sélecteur de régions et le champ de recherche en dessous se ressemblent ».
- **Ce qui est retenu** :
  - La page de recherche n'a plus de champ de saisie. On y trouve le sélecteur de région, puis « Filtres », le nombre d'offres trouvées et les cartes.
  - L'app ne fait plus de recherche par texte sur cette liste, et le message « aucun résultat pour cette recherche » n'y sert plus.
  - **Côté API, rien ne change** : la liste `offer.browse` accepte toujours `search` et sert toujours son texte d'aide. Une autre app, ou un retour arrière, peut s'en servir.
  - Les autres listes gardent leur champ de recherche (« Mes logements », favoris, partenaires…).
- **Tranché par** : humain (enlever le champ) ; session (l'API inchangée)
