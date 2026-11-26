# `2026-11-26` — Mes réservations : ville et logement seulement une région choisie

- **Type** : décision
- **Question posée** : « le filtre ville est visible seulement si une région est sélectionnée », puis « meme le filtre logement est visible que si la région est sélectionnée ».
- **Ce qui est retenu** : les filtres « Ville » et « Logement » n'apparaissent qu'une ou plusieurs régions choisies (`visibleWhen('region', <les régions proposées>)`). La ville ne propose que celles des régions choisies ; le logement, tous ceux des demandes.
- **Corollaire** : sans région proposée (aucun logement avec une région), ville et logement ne s'affichent pas.
- **Tranché par** : humain
