# Avis : après un séjour ou une visite terminé

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« faire les étapes 3 et 4 », après la proposition, avec mes choix par défaut)
- **Complète** : 2026-11-19-contrat-avis-offres.md, 2026-11-22-statuts-terminee-expiree.md

## Décision

- Un membre ne peut donner son avis sur un logement qu'après une demande **terminée** dans ce logement : acceptée, et son dernier jour est passé. Un séjour ou une visite suffit. Sinon : 403 « Vous pourrez donner votre avis après un séjour ou une visite terminé dans ce logement. », et pas de bouton sur la fiche (`can_review` faux).
- Un membre qui a déjà donné son avis peut toujours le modifier ou le retirer. Les avis existants restent.
- Un seul avis par membre et par logement, même après plusieurs séjours, comme avant.
- Sur la page d'une demande terminée, tant que le membre n'a pas donné son avis, le bouton principal devient « Laisser un avis » et ouvre le formulaire d'avis du logement (`/offers/{id}/review`).

## Portée

`OfferPolicy::review` (qui garde aussi la sauvegarde, le retrait et le formulaire), scope `Booking::completed` et `AddBookingToNotificationThread`. Rien ne change côté mobile.
