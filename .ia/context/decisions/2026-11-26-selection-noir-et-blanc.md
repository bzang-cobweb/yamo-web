# `2026-11-26` — Toute sélection d'un champ en noir et blanc

- **Type** : décision
- **Question posée** : « utiliser pour sélectionner un bouton la couleur blanche et le background noir comme les chips, pour tous les types de champs qui sélectionnent (switch, …) ».
- **Ce qui est retenu** : dans l'app, ce qui est choisi dans un champ prend le texte du thème en fond et le fond du thème en texte (noir et blanc en mode clair, inversé en mode sombre), comme les puces (`2026-11-26-filtres-en-puces.md`) :
  - segments (« Tous / Oui / Non ») : l'option choisie en fond noir, texte blanc (avant : la couleur principale violette) ;
  - interrupteur : piste noire une fois allumé, bouton blanc (avant : le vert du système) ;
  - jour choisi des calendriers (`MonthGrid`) : fond noir, texte blanc ;
  - icône de l'option choisie dans une liste d'options : noire.
  Les cases à cocher et les boutons radio étaient déjà en noir. Les boutons d'action (« Appliquer les filtres », « Valider ») gardent la couleur principale : ce ne sont pas des sélections.
- **Tranché par** : humain ; session (boutons d'action hors portée)
