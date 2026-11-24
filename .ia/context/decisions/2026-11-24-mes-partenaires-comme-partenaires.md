# `2026-11-24` — « Mes partenaires » comme la page « Partenaires » ; menu flottant, refermé au toucher

Remplace en partie : 2026-11-24-mes-partenaires-cartes.md, 2026-11-24-mes-partenaires-logo-entier.md, 2026-11-24-logo-entier-sans-cadre.md

- **Type** : décision
- **Question posée** : « reprendre la liste partenaires dans mes partenaires, le statut avant le titre », puis « partenaire sans image dans les listes laisser quand même l'espace réservé au logo », « mes partenaires les 3 petits [points] doivent être flottants », « le dropdown lorsqu'il est ouvert doit se refermer lorsqu'on clique sur l'écran ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - option générique des cartes de liste `ListItem::imageSide()` (`image_layout: side`, image en entier) : l'image en petit (72 points, coins arrondis), à gauche du texte, comme la page « Partenaires » ; « Mes partenaires » l'utilise à la place de l'image 16:9 au-dessus du texte ;
  - le statut n'est plus sur le logo : au-dessus du nom, avec plus d'air (8 points au lieu de 4, « augmenter l'espace entre le statut et le titre ») ;
  - sans logo, sa place reste réservée, vide (sans fond) : les textes restent alignés ;
  - le menu « ⋮ » flotte en haut à droite, sans réserver de place au texte : un nom long passe dessous ;
  - un menu ouvert se referme au toucher ailleurs que sur un bouton ou une carte (entre les cartes, l'intitulé, le vide) et quand la recherche prend la main (`ListSearch.onFocus`), pour toutes les listes du profil.
- **Motif** : la demande de l'humain.
- **Portée** : `ListItem` (module Form), `MyPartnerListing` ; `ListItemCard`, `List`, `ProfileListScreen` de l'app.
- **Corollaires** : la barre du haut et le bouton fixe du bas gardent leur action (ils naviguent) sans d'abord refermer le menu.
- **Tranché par** : humain
