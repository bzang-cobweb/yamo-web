# `2026-11-26` — Filtres « Équipements » et « Règles » en puces

- **Type** : décision
- **Question posée** : « modifier filtre équipements : carrousel avec la liste de ses catégories (bordure avec radius de chaque catégorie, couleur sélection blanche, background noir) ; pareil pour les règles », puis « utiliser chips aussi pour le filtre type », « et type de location », « filtres chambres et salles de bain en chips ».
- **Ce qui est retenu** :
  - un type de champ `chips` (serveur et app) : choix multiples, la valeur est la liste des options choisies, comme `multiselect` ; l'app l'affiche en puces sur une ligne qui défile horizontalement, chacune bordée aux coins arrondis ; choisie, fond noir et texte blanc (le texte et le fond du thème inversés : en mode sombre, fond blanc et texte noir) ;
  - le filtre `property_type` (« Type ») passe de `multiselect` à `chips` ;
  - le filtre `rental_type` (« Type de location ») passe d'un choix unique (`select`, « Tous » ajouté par l'app) à `chips` : plusieurs types à la fois, la valeur devient une liste ; « Libre le » reste affiché sans type choisi, ou quand tous les types choisis sont non meublés ;
  - les filtres `bedrooms` (1, 2, 3, 4+) et `bathrooms` (1, 2, 3+) passent d'un choix unique « au moins N » à `chips` : les nombres choisis, exacts, la dernière puce valant « N ou plus » ; « 2 » et « 3 » choisis gardent les logements de 2 ou 3 chambres (remplace « au moins » de `2026-11-21-salles-de-bain.md`) ;
  - le filtre `feature_flags` (« Équipements ») passe de `multiselect` à `chips` ; il garde les logements qui ont tous les équipements choisis ;
  - les filtres `rule_<id>` (un Oui / Non / Tous par règle) sont remplacés par un seul filtre `rule_flags` (« Règles ») en puces : il garde les logements qui ont toutes les règles choisies cochées.
- **Conséquence** : on ne peut plus chercher « Non » pour une règle (ex. les logements non-fumeurs) ; seulement les logements où la règle est cochée.
- **Remplace en partie** : `2026-11-19-filtres-regles.md` (le Oui / Non par règle).
- **Tranché par** : humain ; session (une puce choisie = règle cochée ; couleurs inversées en mode sombre)
