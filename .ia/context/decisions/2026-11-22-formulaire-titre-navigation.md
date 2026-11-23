# Formulaires : un titre de navigation ; Accepter et Refuser réorganisés

Remplace : 2026-11-22-formulaire-logement-en-sous-titre.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« titre : Marie Curie · du 3 déc. 2026 au 6 déc. 2026 / sous-titre : nom du logement / description : (vide) / titre de navigation : Accepter la demande (prend le titre si vide) »)

## Décision

- Un formulaire peut servir un **titre de navigation**, `navigation_title`, affiché dans la barre du haut. S'il est vide, la barre prend le titre, comme avant.
- Avec un titre de navigation, le contenu commence par le **titre** en gras (18), puis le **sous-titre** en gris, puis la description et la note s'il y en a. Sans lui, rien ne change : le titre va dans la barre du haut, et le sous-titre prend le style de titre en tête du contenu.
- « Accepter la demande » et « Refuser la demande » :
  - titre de navigation : « Accepter la demande » / « Refuser la demande » ;
  - titre : « Marie Curie · du 3 déc. 2026 au 6 déc. 2026 » ;
  - sous-titre : le nom du logement ;
  - pas de description ;
  - tout centré.

## Portée

`Form::navigationTitle()` (nouveau, `null` par défaut), servi par `FormResource`. `BookingAcceptForm` et `BookingRefuseForm`. Mobile : `ProfileFormScreen` (barre du haut), `DynamicForm` (en-tête) et le type `FormDefinition.navigation_title`.
