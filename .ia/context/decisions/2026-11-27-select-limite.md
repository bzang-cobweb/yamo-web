# `2026-11-27` — Liste déroulante à rechercher : une limite par champ, SEARCHABLE_LIMIT par défaut

- **Type** : décision
- **Question posée** : « je veux une limite à SEARCHABLE_LIMIT par défaut ; si je passe une limite, il prend ma limite pour ce select ».
- **Ce qui est retenu** : `Field::searchable(limit: 25)` sert `search_limit` ; sans limite, rien n'est servi. L'app affiche au plus `search_limit` options (la recherche trouve les autres) ; sans, une liste à rechercher en affiche au plus `SEARCHABLE_LIMIT` (10), une liste sans recherche les affiche toutes. « Tous » / « Aucun » n'est pas compté.
- **Portée** : web : `packages/form` (`Field::searchable()`, `search_limit`) ; mobile : `SelectField`, type `FormFieldDefinition.search_limit`.
- **Tranché par** : humain
