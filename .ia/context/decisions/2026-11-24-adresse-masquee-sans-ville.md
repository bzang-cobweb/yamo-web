# `2026-11-24` — Adresse masquée : plus rien, pas même la ville

Remplace : 2026-11-21-adresse-masquee-ville.md

- **Type** : décision
- **Question posée** : « date de la visite, adresse (seulement la ville si le propriétaire masque l'adresse) : annuler cette règle, on n'affiche plus la ville ».
- **Ce qui est retenu** : quand le propriétaire masque l'adresse (`show_address` faux), le demandeur et les visiteurs ne voient ni l'adresse, ni la ville, ni l'indication. C'est la règle de 2026-11-21-afficher-adresse, avant la ville seule.
  - **Détail d'une demande** : pas de ligne « Adresse » pour le demandeur (`BookingThreadDetailsService`).
  - **Fiche de l'offre** : `address` vaut `null`, donc pas de bloc « Localisation » (`OfferDetailResource`).
  - **Le propriétaire** voit toujours l'adresse entière.
- **Corollaire** : la ville reste visible ailleurs, par exemple dans le titre composé (« Location meublée · Studio · Douala ») et dans `location`.
- **Tranché par** : humain (la règle) ; session (appliquée aussi à la fiche de l'offre, où la même règle donnait la ville)
