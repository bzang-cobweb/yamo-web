# `2026-11-26` — Les libellés des « Équipements » d'après `FLAG_TREE['features']`

- **Type** : décision
- **Question posée** : « remplacer AMENITIES constant par FLAG_TREE['features'] ».
- **Ce qui est retenu** :
  - `RentalDomain::AMENITIES` est retiré ; `flagLabelKey` prend le libellé d'une case dans `offer::rental.amenities` si elle est dans `FLAG_TREE['features']` ;
  - `offer::rental.amenities` reçoit `free_parking` et `cameras` (fr, en, de), mêmes textes que les clés de premier niveau ; en allemand, `amenities.generator` devient « Stromaggregat », le libellé utilisé jusqu'ici, pour que les noms des catégories ne changent pas ;
  - les clés de premier niveau (`offer::rental.free_parking`, `cameras`, `generator`, `borehole`) restent : les migrations déjà écrites les utilisent ;
  - la migration `2026_12_07_260000_move_amenities_to_equipment_categories` garde sa propre liste : une migration n'est pas modifiée.
- **Motif** : une seule liste des équipements, celle de l'arbre des cases.
- **Portée** : domaine `rental`, libellés des cases « Équipements ».
- **Corollaires** : un nouvel équipement dans `FLAG_TREE['features']` doit avoir son libellé dans `offer::rental.amenities`, dans chaque langue.
- **Tranché par** : humain (le remplacement) ; session (les libellés ajoutés, l'allemand aligné)
