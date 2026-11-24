# `2026-11-24` — « Devenir partenaire » : la barre fixe de « Partenaires » et « Mes partenaires »

- **Type** : décision
- **Question posée** (mode nuit) : « mettre un bouton (barre bottom fixe) devenir partenaire sur la page mes partenaires pour tous les utilisateurs : connecté → formulaire ajouter un partenaire ; pas connecté → login ».
- **Réponse** : la demande, lue ainsi : un visiteur n'a aucun accès à « Mes partenaires » (liste du profil, connectée) ; le bouton va donc aussi sur la page publique « Partenaires », que voient les visiteurs.
- **Ce qui est retenu** :
  - « Mes partenaires » : la barre fixe dit « Devenir partenaire » (au lieu d'« Ajouter un partenaire ») et ouvre le formulaire d'ajout ; le formulaire garde son titre « Ajouter un partenaire » ;
  - « Partenaires » : une barre fixe « Devenir partenaire », décrite par l'API (`PartnerListing::footer()`) : connecté (et autorisé à créer), le formulaire ; visiteur, la connexion (`/sign-in`) ;
  - l'app ouvre le bouton d'une liste par une seule fonction, `openListFooter()` (formulaire, onglets, connexion, fiche), aussi pour les listes du profil.
- **Motif** : la demande ; « pour tous les utilisateurs » et « pas connecté → login » ne valent que sur une page que voient les visiteurs.
- **Portée** : `PartnerListing`, `MyPartnerListing`, traductions ; `PartnersScreen`, `ProfileListScreen`, `openListFooter` de l'app ; tests.
- **Corollaires** : après la connexion, le visiteur revient à l'écran d'où il venait, sans ouvrir le formulaire tout seul.
- **Tranché par** : humain (demande) ; session de nuit (le bouton aussi sur « Partenaires », lien de connexion)
