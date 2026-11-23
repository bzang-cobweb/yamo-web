Remplacée par : 2026-11-22-formulaire-titre-navigation.md

# Accepter ou refuser une demande : le logement en sous-titre, le membre en description, centrés

Remplace : 2026-11-22-formulaire-description.md (pour l'usage dans « Accepter » et « Refuser »)

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« ajouter le nom de l'offre comme sous-titre du formulaire, pas comme description / centrer le titre, le sous-titre et la description »)

## Décision

Dans les formulaires « Accepter la demande » et « Refuser la demande » :
- le **sous-titre**, en gras en tête du contenu, est le titre saisi du logement (« Studio meublé à Bonanjo ») ;
- la **description**, en gris dessous, donne le membre et les dates : « Marie Curie · du 3 déc. 2026 au 6 déc. 2026 » ;
- l'avertissement, en orange, suit s'il y en a un ;
- tout est centré : le titre de la barre du haut l'est déjà, puis le sous-titre, la description et l'avertissement.

## Portée

`BookingAcceptForm` et `BookingRefuseForm` (`subtitle`, `description`, `titleAlign` = `center`). Rien côté mobile : `DynamicForm` aligne déjà ces textes selon `title_align`.
