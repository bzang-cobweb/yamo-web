# `2026-11-26` — Un seul panneau qui monte du bas : `BottomSheet`

- **Type** : décision
- **Question posée** : « pourquoi utiliser deux panneaux ? pourquoi ce n'est pas un panneau qui rend juste children ? » ; puis « fais le BottomSheet pour que je garde exactement le même rendu que maintenant peu importe children. Il faut tout de même prévoir que BottomSheet puisse avoir une taille adaptée au contenu, mais par défaut taille max comme maintenant (un booléen suffirait) » ; puis « corrige le décalage du panneau du calendrier ».
- **Ce qui est retenu** :
  - `BottomSheet` (`mobile/src/shared/components/`) : le fond assombri qui ferme au toucher, la ligne du titre et de la croix (« Fermer »), bordée en bas, les coins arrondis (20), la marge du bas de l'écran ; puis `children`, sans rien autour ;
  - par défaut, taille maximale : le panneau occupe la fenêtre réelle du `Modal`, moins une bande en haut (la barre d'état et `Spacing.four`), et `children` remplit la place (`flex: 1`) ; `fitContent` : posé en bas, à la taille du contenu, sans dépasser cette place ;
  - les marges viennent de la fenêtre du panneau (un `SafeAreaProvider` dans le `Modal`), pas de l'écran qui l'ouvre : sur un écran de la pile (le calendrier), la fenêtre du `Modal` ne commence pas en haut de l'écran ; la hauteur fixe calculée sur l'écran (`useSheetHeight()`, retiré) faisait commencer le panneau du calendrier plus bas que celui des filtres et le faisait déborder en bas ;
  - le contenu gère son bas : ce qui défile dans un `ScrollView` en `flex: 1`, puis ce qui reste en bas (la barre « Effacer » / « Appliquer » des filtres) ;
  - `overlay` : ce qui se pose par-dessus, dans la même fenêtre (le bandeau des messages du calendrier) ;
  - les filtres (`FilterSheet`) et les demandes d'un jour du calendrier l'utilisent. Le rendu est celui des filtres ; le panneau du calendrier prend donc la croix à la place du lien « Fermer », la bordure sous le titre et les coins de 20 ; son contenu garde ses marges.
- **Motif** : un panneau, un rendu ; les deux copies avaient divergé (fermeture, bordure, coins, marges).
- **Portée** : les panneaux qui montent du bas (app).
- **Corollaires** : un nouveau panneau passe par `BottomSheet` ; un contenu à barre fixe la place après son `ScrollView`.
- **Tranché par** : humain (un seul panneau, `children`, taille maximale par défaut, un booléen pour la taille du contenu) ; session (`fitContent`, `overlay`, le rendu des filtres pour les deux)
