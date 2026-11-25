Remplacée par : 2026-11-25-cases-par-categories.md

# `2026-11-25` — Les Oui / Non d'un logement en choix à trois positions

Remplace : 2026-11-23-regles-oui-non-obligatoires.md

- **Type** : décision
- **Question posée** : « utiliser le choix à trois positions pour animaux acceptés, fumeurs acceptés, fêtes et évènements autorisés, caméras, forage, groupe électrogène ».
- **Ce qui est retenu** :
  - **Six champs** se saisissent en « Non précisé / Oui / Non » (un choix à trois positions), dans l'app comme au back-office :
    - les règles : Animaux acceptés, Fumeurs acceptés, Fêtes et événements autorisés ;
    - les caractéristiques : Caméras de surveillance, Forage (nouveau), Groupe électrogène (nouveau).
  - **Ce qui est enregistré** dans `offers.details` : `"yes"`, `"no"`, ou `null` pour « Non précisé » (`Field::NOT_SPECIFIED`, jamais enregistré). À la modification, rien d'enregistré redevient « Non précisé ».
  - **Les règles ne sont plus obligatoires** : « Non précisé » est une réponse. Sur la fiche, la ligne n'apparaît pas ; dans les filtres, l'offre ne sort ni pour « Oui » ni pour « Non ».
  - **Le groupe électrogène** n'est plus un équipement. Une migration passe les offres qui l'avaient coché à « Oui » (6 en local), et le retire de leurs équipements. Elle est réversible.
  - **Les filtres** ne changent pas : Animaux, Fumeurs et Fêtes y restent ; Caméras, Forage et Groupe électrogène n'y sont pas.
- **Tranché par** : humain (les six champs, trois positions) ; session (« Non précisé » non enregistré, règles facultatives, le groupe électrogène retiré des équipements, filtres inchangés)
