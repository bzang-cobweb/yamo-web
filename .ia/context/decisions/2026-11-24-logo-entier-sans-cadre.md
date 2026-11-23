Remplacée en partie par : 2026-11-24-mes-partenaires-comme-partenaires.md

# `2026-11-24` — Image en entier d'une carte : sans cadre, comme une photo de « Mes logements »

Remplace : 2026-11-24-logo-entier-marge-reduite.md
Remplace en partie : 2026-11-24-mes-partenaires-logo-entier.md

- **Type** : décision
- **Question posée** : « mes partenaires pourquoi le cadre sur le logo a des bordures ? », puis « pourquoi ce cadre alors que j'ai demandé de regarder mes logements ? ».
- **Réponse** : la carte de « Mes logements » est le modèle ; le cadre (bordure et marge) avait été ajouté sans demande.
- **Ce qui est retenu** : une image en entier (`image_fit: contain`, `ItemPhoto`) occupe la même zone 16:9 qu'une photo de « Mes logements », sans bordure ni marge ; seule différence, elle n'est pas rognée. La pastille et le menu passent dessus, comme sur une photo.
- **Motif** : aligner « Mes partenaires » sur « Mes logements » ; la carte a déjà sa bordure.
- **Portée** : `ItemPhoto` de l'app ; toute carte de liste avec `imageContain()` (aujourd'hui « Mes partenaires »).
- **Corollaires** : un logo haut ou carré passe sous la pastille ou le menu, comme une photo.
- **Tranché par** : humain
