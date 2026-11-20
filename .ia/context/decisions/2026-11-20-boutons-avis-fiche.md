# Fiche d'une offre : les boutons d'avis selon les avis

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-19-contrat-avis-offres.md

## Décision

Pour un membre qui peut donner un avis (`can_review`) :

| Avis de l'offre | Section « Avis » | Barre fixe du bas |
|---|---|---|
| Aucun | « Donner mon avis » | — |
| Un seul, le sien | « Modifier mon avis » | — |
| Un ou plusieurs, aucun du membre | « Voir tous les avis » | « Donner mon avis » |
| Plusieurs, dont le sien | « Voir tous les avis » | « Modifier mon avis », « Supprimer mon avis » |

La suppression de son avis n'est plus dans la section. Les boutons d'avis de la barre
sont sur une ligne au-dessus du prix et du bouton de réservation.

« Voir tous les avis » s'affiche dès qu'il y a un avis, sauf un seul qui est le sien,
même quand tous les avis sont servis sur la fiche ; un visiteur et le propriétaire le
voient aussi, sans autre bouton.

Règle portée par `reviewActions()` (`mobile/src/features/offer/components/OfferReviews.tsx`).

## Motif

Demande de l'humain du 2026-11-20.

## Portée

La fiche d'une offre dans l'app. Pas la liste complète des avis.

## Corollaires

- Remplace, pour la fiche, la règle « Voir tous les avis seulement s'il y a plus d'avis
  que ceux servis » (2026-11-19-contrat-paiement-visibilite-avis).
- Un seul avis, le sien : il ne peut plus être supprimé depuis la fiche.
