Remplacée en partie par : 2026-11-18-titre-liste-tiret.md (tiret avant la ville).


# `2026-11-18` — Logement : titre de liste construit (type, meublé, ville)

- **Type** : décision
- **Question posée** : aucune. Consigne de l'humain :
  > pour le logement, le titre de ListItem doit être 1. le type 2. "meublé" si coché 3. la ville
- **Ce qui est retenu** :
  - **un domaine peut construire le titre d'une offre dans les listes** (`Domain::itemTitle()`). Sinon, c'est le titre saisi ;
  - **pour la location,** le titre est le type, puis « meublé » accordé au type s'il est coché, puis la ville. Exemples :
    - « Studio meublé à Douala » ;
    - « Maison meublée à Yaoundé » ;
    - sans adresse, le type seul ;
    - en anglais, « Furnished studio in Douala » ;
  - **il s'applique à la vitrine et à « Mes logements »** ;
  - **la ville n'est plus en sous-titre de la vitrine,** puisqu'elle est dans le titre ; le sous-titre est le prix ;
  - **la recherche de la vitrine porte aussi sur la ville** (« Rechercher par titre ou ville… »).
- **Motif** : en location, ce qui compte d'un coup d'œil, c'est le type, s'il est meublé, et où.
- **Portée** : module Offer (domaines, listes).
- **Corollaires** :
  - la fiche garde le titre saisi par le propriétaire ;
  - la recherche porte encore sur le titre saisi, en plus de la ville.
- **Tranché par** : humain
