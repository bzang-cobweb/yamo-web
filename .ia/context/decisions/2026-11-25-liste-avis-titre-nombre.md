Remplacée par : 2026-11-25-liste-avis-generique.md

# `2026-11-25` — La liste des avis : la note en titre, le nombre avant la liste, sans croix

- **Type** : décision
- **Question posée** : « page liste des avis enlever le bouton de fermeture. mettre le rating en titre centré. avant la liste mettre x avis ».
- **Ce qui est retenu** :
  - **En-tête** : la flèche de retour seule, sans croix. `BackHeader` accepte `closable={false}`.
  - **Titre**, centré dans l'en-tête : « Avis (4,7) », comme la section de la fiche. Le serveur le sert avec la première page des avis (`title`), construit par `Offer::reviewsTitle()`, qui sert aussi la fiche.
  - **Avant la liste**, centré en gras : « 3 avis » (`heading`, au pluriel selon le nombre, dans la langue).
- **Tranché par** : humain ; session (la note dans le titre de l'en-tête plutôt qu'un bloc à part, le texte « Avis (4,7) »)
