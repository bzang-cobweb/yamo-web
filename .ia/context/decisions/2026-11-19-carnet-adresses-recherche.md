Remplace en partie : 2026-11-19-contrat-adresse-offre-recherche.md (« le carnet d'adresses reste sans suggestions »).

# `2026-11-19` — Carnet d'adresses : la rue propose les adresses connues

- **Type** : décision
- **Question posée** : « la recherche d'adresse ne se fait plus dans le formulaire d'ajout d'une adresse ».
- **Réponse** : la remettre.
- **Ce qui est retenu** : dans le formulaire « Ajouter / Modifier l'adresse » du compte (`auth.address`), la rue propose les adresses connues du pays (`GET /v1/addresses/suggestions`, la même route que pour une offre) ; en choisir une remplit le NPA et la ville. L'aide de la rue le dit (3 caractères au moins).
- **Motif** : la demande de l'humain.
- **Portée** : `UserAddressForm` ; rien dans l'app (champ texte à suggestions déjà générique).
- **Corollaires** : le risque assumé du contrat de l'offre vaut ici aussi (toutes les adresses du pays sont proposées, sans nom ni compte) ; les garde-fous sont les mêmes (connecté, 3 caractères, 10 résultats, appels limités).
- **Tranché par** : humain
