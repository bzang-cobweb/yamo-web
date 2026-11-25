# `2026-11-25` — Une carte « Devenir partenaire » au bout du carrousel de l'accueil

- **Type** : décision
- **Question posée** : « comment mettre en avant sur l'accueil la possibilité de devenir partenaire ».
- **Réponse** : « Carte dans le carrousel » (parmi : une carte dans le carrousel, un encart sous le carrousel, une carte plus haut dans l'accueil).
- **Ce qui est retenu** :
  - La dernière carte du carrousel des partenaires est « Devenir partenaire » :
    - elle a la taille d'un logo, une bordure en pointillés, un « + » et le libellé, dans la couleur principale ;
    - connecté, elle ouvre le formulaire d'ajout ; visiteur, la connexion.
  - L'action vient de l'API : `GET /api/v1/partners` renvoie `meta.become`, qui est le pied de la page « Partenaires » (`PartnerListing::footer()`, décision 2026-11-24-devenir-partenaire). La règle reste à un seul endroit.
  - Sans partenaire, le carrousel s'affiche avec cette seule carte. Sans partenaire ni carte (un compte sans droit de créer), rien ne s'affiche.
  - L'app recharge les partenaires de l'accueil quand le compte change (connexion, déconnexion).
- **Tranché par** : humain (l'emplacement) ; session (la forme de la carte, l'action servie par l'API)
