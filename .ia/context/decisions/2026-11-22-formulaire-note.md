# Formulaires : une note à part sous le titre ; l'avertissement d'acceptation y passe

Remplace : 2026-11-22-accepter-avertissement-sous-titre.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« mettre l'avertissement en dessous du titre », puis « non, titre du formulaire »)

## Décision

- Un formulaire peut servir une **note**, `notice`. C'est un texte à part, affiché sous le titre (et sous le sous-titre, qui sert de titre en tête du contenu dans les formulaires du profil), en petit et en orange, la couleur des avertissements.
- Le formulaire « Accepter la demande » s'en sert. Le titre en gras du contenu reste « Marie Curie · du 3 déc. 2026 au 6 déc. 2026 ». Dessous, la note donne l'avertissement : « 2 autres demandes chevauchent ces dates et seront refusées. », « 2 autres visites sont demandées ce jour-là. ». L'avertissement ne fait donc plus partie du titre.

## Portée

`Form::notice()` (nouveau, `null` par défaut), servi par `FormResource`. `BookingAcceptForm::notice` et `subtitle`. Mobile : `DynamicForm` (`form-notice`) et le type `FormDefinition.notice`.
