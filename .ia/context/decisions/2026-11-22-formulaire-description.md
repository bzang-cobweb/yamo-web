# Formulaires : une description sous le sous-titre ; le titre du logement pour accepter ou refuser

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« mettre le titre de l'offre comme sous-titre du formulaire », puis « non, le titre actuel reste ; est-ce que le formulaire n'a pas un champ sous-titre ou description pour mettre le titre de l'offre ? »)
- **Complète** : 2026-11-22-formulaire-note.md

## Décision

- Un formulaire peut servir une **description**, `description`. C'est un texte en gris, sous le sous-titre (qui sert de titre en gras en tête du contenu) et avant la note.
- « Accepter la demande » et « Refuser la demande » s'en servent pour le titre saisi du logement. La page se lit : « Marie Curie · du 3 déc. 2026 au 6 déc. 2026 » en gras, « Studio meublé à Bonanjo » en gris, puis l'avertissement en orange s'il y en a un.

## Portée

`Form::description()` (nouveau, `null` par défaut), servi par `FormResource`. `BookingAcceptForm` et `BookingRefuseForm`. Mobile : `DynamicForm` (`form-description`). Au passage, le rouge partagé passe dans `shared/theme/colors.ts`, pour casser la boucle d'imports entre `FieldShell` et `StatusTag`.
