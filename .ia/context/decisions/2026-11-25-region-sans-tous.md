# `2026-11-25` — Le filtre de la région sans l'option « Tous »

- **Type** : décision
- **Question posée** : « enlever l'option "tous" dans le sélecteur des régions des filtres de la recherche ».
- **Ce qui est retenu** :
  - Un champ peut se servir sans l'option « Tous » que l'app ajoute en tête d'un filtre déroulant : `Field::withoutAllOption()`, qui sert `all_option: false`.
  - Le filtre de la région s'en sert dans les listes d'offres, qui partagent une seule définition de filtres : la recherche, « Mes logements » et les favoris.
  - On y choisit une région, et « Effacer les filtres » la retire.
  - Les autres filtres gardent « Tous », dont la ville et le filtre de la région des réservations.
- **Tranché par** : humain (sans « Tous ») ; session (les listes d'offres, qui partagent leurs filtres)
