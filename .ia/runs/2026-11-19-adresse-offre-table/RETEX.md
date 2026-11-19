# RETEX — adresse d'une offre : table offer_addresses

- **Migration et SQLite** : une migration qui crée une table liée en cascade, puis modifie la table parente, perd ses données sous SQLite (la table parente est reconstruite). Règle candidate pour le projet : lire les données à recopier avant toute modification de schéma, écrire après.
- **Tests à état périmé** : un test a supprimé une adresse déjà quittée par l'offre (relation chargée avant un second envoi). Relire avec `fresh()` après chaque requête.
- **zsh** : une liste de chemins dans une variable n'est pas découpée (`git add -- $F` échoue) ; utiliser un tableau `"${F[@]}"`. Deuxième fois que zsh surprend (`--include=*.php` à citer) : règle candidate pour les commandes de session.
- **Règle ré-implémentée / domiciliée** : « une liste n'est pas une traduction » trouve son domicile dans `Offer::detailIn` (cause du bug des équipements non cochés).
- **Demandes reçues en cours de chantier** : mises en file et traitées après le commit du chantier, pour ne pas mêler les commits pendant la QA.
