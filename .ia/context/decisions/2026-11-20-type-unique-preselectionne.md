# Un type de notification unique est choisi d'emblée

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-filtre-option-unique.md (qui reste : un filtre n'est jamais verrouillé et n'a pas de valeur par défaut)

## Décision

- `Field::selected($value)` : un filtre de liste choisi d'emblée, servi dans `selected`
  (seulement quand il est posé). Distinct de `default`, qui vaut « Tous » pour un filtre
  dans l'app.
- Le filtre « Type » des notifications le pose quand il n'a qu'une option
  (`NotificationFilters::fields()`).
- L'app (`setFilterFields`) l'applique à la première réception des filtres d'une liste,
  comme un choix : il filtre, compte dans le badge ; un choix déjà fait (le compteur
  « x demandes en attente ») n'est pas écrasé ; « Tous » choisi ensuite n'est pas
  réappliqué.

## Motif

Demande de l'humain du 2026-11-20 : « dans les filtres sur la liste des notifications, si un
seul type le sélectionner le type par défaut ». Limité au filtre « Type » (décidé seul) : un
« Logement » unique choisi d'emblée cacherait les autres notifications.

## Corollaires

- Avec un type unique choisi, les notifications d'un autre type (inconnu des modules) ne
  s'affichent qu'en choisissant « Tous ».
- La première page de la liste part sans filtre ; dès que les filtres arrivent, la liste
  est relue avec le type.
