# `2026-11-19` — Filtres de la vitrine : les règles Oui / Non

- **Type** : décision
- **Question posée** : « ajouter les filtres boolean qui sont dans le tab règles ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - trois filtres de la vitrine, comme « Meublé » (Tous / Oui / Non, « Tous » par défaut) : « Animaux acceptés », « Fumeurs acceptés », « Fêtes et événements autorisés », après « Équipements » ;
  - « Oui » ou « Non » ne garde que les offres dont le propriétaire a répondu ainsi ; une règle non renseignée ne répond ni oui ni non (exclue dès qu'on filtre) ; « Tous » ou une valeur inconnue ne filtre rien ;
  - la liste de ces règles est définie une fois (`RentalDomain::YES_NO_RULES`), pour la saisie et les filtres.
- **Motif** : la demande de l'humain ; le traitement d'une règle non renseignée est un choix de l'agent.
- **Portée** : `RentalDomain` (`filters()`, `applyFilters()`) ; rien dans l'app (filtres décrits par le serveur).
- **Corollaires** : « Documents demandés » (choix multiple) n'est pas un filtre.
- **Tranché par** : humain ; règle non renseignée par l'agent
