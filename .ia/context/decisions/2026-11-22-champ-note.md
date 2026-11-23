# Champs de formulaire : une note avant le libellé

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« la note peut aussi être une propriété d'un champ à afficher avant le label du champ »)
- **Complète** : 2026-11-22-formulaire-note.md

## Décision

Un champ peut porter une note, `Field::notice()`, servie en `notice`. C'est un texte affiché avant son libellé, en petit et en orange, comme la note d'un formulaire. Elle sert, par exemple, à avertir juste avant une case à cocher. Aucune note par défaut.

## Portée

`Field` (serveur). Mobile : `FieldShell`, commun à tous les types de champ, et le type `FormFieldDefinition.notice`.
