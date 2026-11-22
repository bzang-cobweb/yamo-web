# Recherche : « Rechercher… » partout

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« recherche placeholder rechercher partout »)

## Décision

Le texte d'aide du champ de recherche est « Rechercher… » sur toutes les listes (en : « Search… », de : « Suchen… ») : accueil, favoris, Mes logements, Mes réservations, Demandes d'un logement, adresses, notifications. Toutes les listes lisent la même clé, `form::mobile.form.search`.

Les textes propres à chaque liste (« Rechercher par titre ou ville… », « Rechercher un membre… », « Rechercher une adresse… »…) sont retirés.

## Motif

Un seul texte, le même partout.

## Portée

Les `searchPlaceholder()` des listes. Ce qu'on peut chercher dans chaque liste ne change pas. Le bouton « Rechercher un logement » des listes vides n'est pas un champ de recherche : il reste.
