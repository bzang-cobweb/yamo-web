# « Ajouter une adresse » en bouton dans la barre fixe du bas

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Remplace en partie** : 2026-11-18-lien-creation-sous-recherche.md (pour « Mes adresses »)

## Décision

Dans la liste « Mes adresses », « Ajouter une adresse » est un bouton dans la barre fixe du bas
(`footer` de `UserAddressListing`, qui ouvre le formulaire `auth.address`), et non plus un lien
sous la recherche (`create` n'est plus servi). L'app n'a pas changé : elle affiche déjà le
`footer` d'une liste en bouton fixe.

## Motif

Demande de l'humain du 2026-11-21 : « dans mes adresses, déplacer le lien ajouter une adresse
au footer fixe en bouton ». Limité à « Mes adresses » : les autres listes gardent leur lien.
