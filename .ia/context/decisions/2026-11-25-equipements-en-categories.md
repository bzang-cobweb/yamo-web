# `2026-11-25` — Les anciens équipements deviennent des cases de « Équipements »

- **Type** : décision
- **Question posée** : « reprendre tous les anciens équipements (wifi, …) et les mettre dans le seeder ».
- **Ce qui est retenu** :
  - **Les 12 anciens équipements** (Wifi, Parking, Climatisation, Eau chaude, Réserve d'eau / forage, Gardien / sécurité, Cuisine équipée, Lave-linge, Fer à repasser, Télévision, Balcon / terrasse, Piscine) sont des enfants de la catégorie « Équipements ». Ils sont créés par le seeder, en tête, suivis de Caméras de surveillance, Forage, Groupe électrogène.
  - **Le champ à part « Équipements »** (liste à choix multiples `amenities`) est retiré : il n'y a plus qu'un champ, les cases « Équipements ».
  - **Les données** : une migration passe les codes de `details.amenities` en ids de cases dans `feature_flags`, et renumérote les cases dans l'ordre de départ. Les données de démonstration écrivent les ids.
  - **La fiche** : les équipements restent sur une ligne, « Équipements : Wifi, Parking, Forage » ; les règles gardent une ligne « Oui » par case.
  - **Les filtres** : « Équipements » cherche par id de case ; toutes les cases choisies doivent être cochées.
  - **« Forage »** a ensuite été retiré, en double avec « Réserve d'eau / forage » (décision 2026-11-25-forage-en-double).
- **Tranché par** : humain ; session (une ligne pour les équipements sur la fiche, l'ordre, pas de fusion)
