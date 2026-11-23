Remplace en partie : 2026-11-19-onglet-prix.md (contenu de l'onglet « Prix »).

# `2026-11-23` — Back-office d'une offre : caution et avance de loyer dans l'onglet « Prix »

- **Type** : décision
- **Question posée** : « déplacer les champs caution et avance loyer sous le tab prix sur le formulaire backend ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : l'onglet « Prix » montre le loyer, puis les charges, la caution en mois, l'avance de loyer en mois et la caution d'un meublé (`RentalDomain::priceAttributes()`). Chacun garde sa condition d'affichage selon le type de location.
- **Motif** : la demande de l'humain.
- **Portée** : `OfferForm` du back-office.
- **Corollaires** : aucun changement de données, d'API ni de l'app.
- **Tranché par** : humain
