# « Retirer » un logement : depuis sa fiche, à la place du prix

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« enlever le lien retirer et le mettre dans le détail de l'offre à la place du prix sur le footer »)
- **Complète** : 2026-11-22-logements-carte-vers-fiche.md

## Décision

- Les cartes de « Mes logements » n'ont plus « Retirer » dans leur menu trois points.
- Sur la fiche de son propre logement, la barre fixe du bas affiche « Retirer », en rouge, à la place du prix ; « Modifier » reste à droite. Le toucher demande « Retirer cette offre ? ». Si on confirme, le logement est retiré (suppression douce, comme avant), l'app affiche « Offre retirée. » et revient en arrière.

## Portée

- `OfferDetailResource` sert `delete` (libellé, confirmation, envoi `DELETE /v1/offers/{id}`) à l'auteur qui peut supprimer, et `null` aux autres. `OfferListing` ne sert plus `delete`.
- Mobile : la barre du bas de `OfferDetailScreen`.

## Corollaires

Le retrait ne fait rien de plus qu'avant : les demandes du logement restent telles quelles.
