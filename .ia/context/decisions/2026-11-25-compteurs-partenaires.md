# `2026-11-25` — Les compteurs de vues et de clics des partenaires

- **Type** : décision
- **Question posée** : « compteur voir et click » (sur les partenaires), puis : « la vue augmente uniquement à la présence dans le carousel, à la présence dans la liste partenaires ; le clic augmente uniquement quand on clique sur un lien qui ouvre la fiche d'un partenaire ; aucune augmentation si c'est un partner_user ».
- **Ce qui est retenu** :
  - **Vue** : un partenaire compte une vue s'il est servi :
    - dans le carrousel de l'accueil (`GET /api/v1/partners`) ;
    - ou dans une page de la liste « Partenaires » (`partner.all`, à chaque page chargée, recherche comprise).
  - Ouvrir sa fiche ne compte pas de vue.
  - Le crochet `Listing::served()` (vide par défaut) permet à une liste de compter ce qu'elle a affiché.
  - **Clic** : un partenaire compte un clic quand on touche le lien qui ouvre sa fiche, depuis le carrousel de l'accueil ou depuis la liste « Partenaires ».
    - L'app appelle alors `POST /api/v1/partners/{id}/click`.
    - Le bouton de la fiche qui ouvre son site ne compte plus de clic.
    - Ouvrir la fiche autrement ne compte rien : un lien direct, ou « Mes partenaires ».
  - **Comptes du partenaire** : un compte rattaché au partenaire (`partner_user`) ne compte ni vue ni clic pour ce partenaire. Il compte normalement pour les autres partenaires. Le clic répond quand même 204.
- **Remplace** : la règle de 2026-11-23-module-partenaires, où le clic était compté au bouton du lien et la page « Partenaires » ne comptait pas de vue.
- **Portée** : les compteurs `views_count` et `clicks_count` montrés au back-office.
- **Tranché par** : humain
