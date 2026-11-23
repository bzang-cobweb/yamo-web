# Fiche d'un logement : la note sous le titre composé

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« ajouter la note après le titre composé centré »)
- **Complète** : 2026-11-22-fiche-titres-centres.md

## Décision

Sous le titre composé de la fiche, centrée, la note du logement : les étoiles, la moyenne et le nombre d'avis (« ★★★★★ 4,7 (3) »), comme en tête de la section Avis. Sans avis, rien.

## Portée

Mobile : `OfferDetailScreen` (`TitleRating`), qui lit `reviews.average` et `reviews.count`, déjà servis.
