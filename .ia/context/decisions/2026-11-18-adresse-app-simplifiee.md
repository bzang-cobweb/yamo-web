Remplacée en partie par : 2026-11-18-bouton-ajout-fixe.md (point 3).


# `2026-11-18` — Adresse dans l'app : sans coordonnées ni suggestions ; bouton d'ajout en haut à droite

- **Type** : décision
- **Remplace en partie** : `2026-11-18-adresse-du-compte-modele.md` (suggestions dans l'app) et `2026-11-18-adresses-liste-generique.md` (latitude et longitude saisies dans l'app).
- **Question posée** : aucune ; consigne de l'humain après la clôture du chantier `2026-11-18-adresse-du-compte-modele`.
- **Réponse** :
  > 1. mettre les champs latitude et longitude uniquement dans le formulaire backoffice
  > 2. autocomplete seulement en backoffice
  > 3. placer le bouton d'ajout avant la liste aligné à droite
- **Lecture retenue** :
  - le formulaire `location.address` servi à l'app n'a plus ni latitude ni longitude. Une adresse créée depuis l'app n'a pas de coordonnées ; une adresse existante garde les siennes ;
  - la rue n'a plus de suggestions dans l'app, et le point d'API `GET /v1/addresses/suggestions` est supprimé : le risque assumé de découverte des adresses par un compte connecté tombe ;
  - le back-office garde latitude, longitude et suggestions ;
  - dans l'écran de liste générique de l'app, le bouton de création passe au-dessus des items, aligné à droite.
- **Motif** : les coordonnées et la recherche d'adresses existantes sont des outils d'administration.
- **Portée** : module Location (formulaire de l'app, API), app mobile (écran de liste).
- **Corollaires** :
  - la capacité générique « champ à suggestions » (`Field::suggestions()`, `SuggestionList` dans l'app) reste disponible, mais aucun formulaire de l'app ne l'utilise ;
  - `AddressPolicy` ne permet plus rien hors du back-office.
- **Tranché par** : humain
