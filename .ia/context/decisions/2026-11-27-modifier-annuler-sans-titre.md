# `2026-11-27` — Modifier et annuler une demande : ni titre ni sous-titre dans le formulaire

Remplace en partie : 2026-11-23-modifier-annuler-comme-accepter.md (le titre du logement en tête du formulaire ; l'action reste dans la barre du haut)

- **Type** : décision
- **Question posée** : « ma réservation formulaires pour annuler et modifier une demande, enlever le titre et le sous titre du formulaire ».
- **Ce qui est retenu** : les formulaires `offer.booking.edit` et `offer.booking.cancel` n'ont plus le nom du logement en titre ni le titre composé (« Location meublée · Studio · Douala ») en sous-titre ; seuls les champs, sous le titre du panneau (« Modifier la demande de séjour », « Annuler la demande de visite »).
- **Corollaire** : `OfferBookingForm::title()` peut renvoyer `null`, comme `Form::title()`.
- **Tranché par** : humain
