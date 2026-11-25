# `2026-11-25` — Les vedettes, premier critère d'ordre de la recherche et des partenaires

- **Type** : décision
- **Question posée** : « dans la recherche et partenaires, les vedettes sont le premier critère d'ordre ».
- **Réponses** : partenaires : « Oui, comme les offres » (ajouter « En vedette jusqu'au ») ; recherche : « Toutes, sans limite ».
- **Ce qui est retenu** :
  - **Recherche** : toutes les offres en vedette sont épinglées en tête de la première page, la date la plus lointaine d'abord. Il n'y a plus de limite de 20. Viennent ensuite les autres, les plus récentes d'abord. La recherche et les filtres s'appliquent aux vedettes.
  - **Partenaires** : un nouveau champ « En vedette jusqu'au » (`featured_until`, date et heure, vide : pas en vedette) :
    - au back-office, dans l'onglet « Campagne », et en colonne de la liste ;
    - migration additive.
  - Sur la page « Partenaires », les vedettes sont en tête, la date la plus lointaine d'abord, puis l'ordre du back-office.
  - Sur le carrousel de l'accueil, les vedettes d'abord, tirées au hasard, dans la limite du nombre réglé. Les autres places sont tirées au hasard parmi les autres partenaires. L'affichage suit le même ordre.
  - Une vedette passée reprend sa place.
  - Il n'y a pas de badge, comme pour les offres.
- **Remplace** : la limite de 20 vedettes épinglées de la recherche (décision 2026-11-19-contrat-vedette-favoris).
- **Tranché par** : humain
