Remplace en partie : 2026-11-24-partenaires-par-les-membres.md (cartes et formulaire de « Mes partenaires »).

# `2026-11-24` — « Mes partenaires » : cartes, menu et formulaire en étapes

- **Type** : décision
- **Question posée** : « mes partenaires : cards mettre le statut flottant en haut à gauche ; limiter la description à une ligne ; x partenaires ; dropdown 3 petits points avec lien modifier et retirer ; le click sur la card va sur la page de détail du partner ; formulaire de partner par étape (1 : logo, nom, description, 2 : téléphone, e-mail, lien) ; modifier un partner : le sélecteur de langue comme pour les offres (devient étape 1) », puis « centre la note, le titre, le sous-titre et la description dans le formulaire ».
- **Réponse** : les demandes elles-mêmes.
- **Ce qui est retenu** :
  - cartes : le statut flottant en haut à gauche du logo ; la description sur une ligne (`ListItem::subtitleLines()`, `subtitle_lines` servi à l'app, option générique) ; « 3 partenaires » en tête ; un menu ⋮ avec « Modifier » et, pour un administrateur, « Retirer » ; toucher la carte ouvre la page du partenaire ;
  - la page d'un partenaire s'ouvre aussi, quel que soit son statut, pour les comptes qui lui sont rattachés ;
  - le formulaire en étapes : « Partenaire » (logo, nom, description), « Contact » (téléphone, e-mail, lien et libellé du bouton) ; à la modification, l'étape « Langue » d'abord, comme une offre : chaque langue « traduite » ou « à traduire », la choisir recharge le formulaire pré-rempli, l'envoi n'écrit que cette langue ;
  - la note, le titre, le sous-titre et la description du formulaire centrés.
- **Motif** : les demandes de l'humain.
- **Portée** : `MyPartnerListing`, `PartnerForm`, `MyPartnerController`, `PartnerService::find()`, `ListItem` ; `ListItemCard` de l'app.
- **Corollaires** : le libellé du bouton reste à l'étape « Contact », avec le lien qu'il ouvre.
- **Tranché par** : humain
