# `2026-11-24` — Image en entier d'une carte : marge réduite

Remplace en partie : 2026-11-24-mes-partenaires-logo-entier.md

- **Type** : décision
- **Question posée** : les logos de « Mes partenaires » « sont plus petites et n'occupent plus toute la place » ; réduire la marge du cadre ?
- **Réponse** : « Oui, réduis-la ».
- **Ce qui est retenu** : le cadre d'une image en entier (`image_fit: contain`, `ItemPhoto`) a une marge de 16 points de chaque côté, au lieu de 44 points en haut et en bas ; la pastille et le menu passent au-dessus de l'image, comme sur une photo.
- **Motif** : la marge de 44 points laissait au logo à peine la moitié de la hauteur de la carte. La taille affichée ne dépend pas des pixels de l'image (`contentFit="contain"`) : les conversions n'y étaient pour rien.
- **Portée** : `ItemPhoto` de l'app ; toute carte de liste avec `imageContain()` (aujourd'hui « Mes partenaires »).
- **Corollaires** : un logo carré ou haut peut passer sous la pastille ou le menu.
- **Tranché par** : humain
