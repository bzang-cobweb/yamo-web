# `2026-11-25` — Le bloc « À la une » en haut de l'accueil

- **Type** : décision
- **Question posée** : « faire le bloc à la une ». L'option proposée : les vedettes d'abord, tirées au hasard, complétées par des offres récentes bien notées ; 2 par défaut, réglable jusqu'à 4.
- **Réponse** : « vedettes au hasard seulement si paiement activé autrement compléter. le bloc se place en haut de l'accueil avant les offres par ville avec de grandes cartes ».
- **Ce qui est retenu** :
  - `GET /api/v1/home` renvoie `featured`, avant `carousels`.
  - Les offres en vedette (`featured_until` à venir) sont toujours tirées au hasard.
  - Paiement des propriétaires appliqué (`Setting::paymentRequired()`) : seulement ces offres. Sans vedette, le bloc est vide et l'app ne l'affiche pas.
  - Sinon, les places restantes vont aux offres les mieux notées, les plus récentes (`published_at`) à note égale. Les offres sans avis viennent après les offres notées.
  - Le nombre d'offres se règle dans « Application », onglet de l'accueil : « Offres « À la une » », de 2 à 4, et 2 s'il est vide (`settings.home_featured`, migration additive).
  - Dans l'app, le bloc est en haut de l'accueil, au-dessus des villes, avec le titre « À la une ». Ce n'est pas un carrousel : ses cartes ont la taille de celles des villes, deux par ligne, toutes visibles (1 ou 2 lignes). La photo est à la conversion `home`.
  - Le bloc et les villes viennent d'une seule requête. Tirer vers le bas refait les deux tirages.
- **Motif** : la vedette est ce que paient les propriétaires quand le paiement est appliqué, et ce bloc en est la vitrine. Sans paiement, il ne doit pas rester vide.
- **Portée** : l'accueil de l'app.
- **Corollaires** : la recherche garde ses vedettes épinglées en tête (décision 2026-11-19-contrat-vedette-favoris). Il n'y a toujours pas de badge.
- **Tranché par** : humain (la règle du paiement, l'emplacement ; « enlever le carousel et faire des cards de la taille comme celui du carousel des villes ») ; session (l'ordre de complément, le réglage de 2 à 4)
