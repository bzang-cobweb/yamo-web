Remplacée en partie par : 2026-11-24-logo-entier-marge-reduite.md, 2026-11-24-logo-entier-sans-cadre.md


# `2026-11-24` — « Mes partenaires » : le logo en entier

- **Type** : décision
- **Question posée** : « est-ce qu'on exige des logos carrés ? » → « corrige l'affichage du logo dans mes partenaires ».
- **Réponse** : pas de logo carré exigé ; l'affichage est corrigé.
- **Ce qui est retenu** : option générique des cartes de liste `ListItem::imageContain()` (`image_fit: contain`) : l'image en entier, au centre de son emplacement 16:9, dans un cadre, avec une marge pour la pastille et le menu ; sans l'option, l'image reste remplie comme une photo. « Mes partenaires » l'utilise pour le logo, qui n'est plus rogné.
- **Motif** : un logo est souvent horizontal ; le remplir le coupait.
- **Portée** : `ListItem` ; `ItemPhoto`, `ItemPicture` de l'app ; `MyPartnerListing`.
- **Corollaires** : aucun recadrage n'est imposé à l'envoi.
- **Tranché par** : humain
