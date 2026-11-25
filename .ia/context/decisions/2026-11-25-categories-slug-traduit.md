# `2026-11-25` — Catégories d'offre : slug traduit, unique par parent ; les cases enregistrées par id

Remplace : 2026-11-25-cases-par-categories.md (pour l'enregistrement et l'identification des racines ; les cases à cocher et les deux racines restent)

- **Type** : décision
- **Question posée** : « translate OfferCategory slug », précisé en « Spatie HasTranslatableSlug sur le champ slug, unique key slug, parent_id » ; puis, pour la clé d'enregistrement d'une case, « l'id de la catégorie » ; puis « renomme caractéristiques par équipements ».
- **Ce qui est retenu** :
  - **Le slug** d'une catégorie est traduit (`HasTranslatableSlug`) : un par langue, tiré du nom de cette langue (« animaux-acceptes », « pets-allowed »…), unique parmi les catégories du même parent (`extraScope` sur `parent_id`). Il ne change plus quand le nom change.
    - Au back-office, le champ s'appelle « Slug » : absent à la création (il est tiré du nom), montré à la modification en lecture seule, un par langue. Un enregistrement ne l'efface ni ne le modifie (« renommer le champ identifiant par slug… l'afficher seulement pendant l'édition, en readonly »).
    - L'index unique sur `slug` seul est retiré ; la colonne devient JSON.
  - **Les cases** d'une offre s'enregistrent par **id de catégorie**, sous le nom du champ : `"rule_flags": ["7"]`, `"feature_flags": ["14", "12"]`.
    - Une migration convertit les anciennes valeurs (`"pets": "yes"` → l'id de « Animaux acceptés »). Les catégories ne sont créées que si une offre en a besoin.
    - Une case qui n'existe plus n'est ni montrée ni cochée.
  - **Les racines** sont retrouvées par leur slug dans la langue par défaut (« regles », « equipements ») ; le slug ne pouvant plus changer, rien ne peut se perdre. Les offres, elles, ne dépendent que des ids. Le seeder retrouve une catégorie par son nom dans la langue par défaut, sous son parent ou ailleurs si elle a été déplacée.
  - **Les filtres de règles** : un par case de « Règles », nommé `rule_<id>`. Une case ajoutée au back-office a son filtre.
  - **« Caractéristiques » devient « Équipements »** (fr), « Amenities » (en), « Ausstattung » (de) : nom et slug, avec une migration.
- **Tranché par** : humain (slug traduit, unicité par parent, clé = id, le renommage) ; session (les migrations, le slug figé après création, l'identification des racines)
