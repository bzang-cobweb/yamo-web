Remplace en partie : 2026-11-19-backoffice-loyer-type-annonce.md (le loyer et les charges quittent « Caractéristiques ») ; 2026-11-19-onglets-localisation-informations.md (« Annonce » devient « Général »).

# `2026-11-19` — Back-office d'une offre : le prix dans « Général »

- **Type** : décision
- **Question posée** : « déplacer le prix et les charges dans le tab annonce en backoffice. renommer le tab annonce par Général ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - l'onglet « Annonce » s'appelle « Général » (« General », « Allgemein ») ;
  - le prix (« Loyer ») puis les charges y viennent après la catégorie ; ils quittent « Caractéristiques » ;
  - le domaine déclare les attributs qui accompagnent le prix (`Domain::priceAttributes()`, `['charges']` pour le logement), à la place de `priceBeforeAttribute()`.
- **Motif** : la demande de l'humain.
- **Portée** : `OfferForm` du back-office, `Domain`, `RentalDomain`, textes `offer::offer.tabs`.
- **Corollaires** : aucun changement de données, d'API ni de l'app.
- **Tranché par** : humain
