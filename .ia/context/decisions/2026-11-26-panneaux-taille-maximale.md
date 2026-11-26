# `2026-11-26` — Les panneaux qui montent du bas s'ouvrent à leur taille maximale

- **Type** : décision
- **Question posée** : « ouvrir la fenêtre qui s'ouvre par-dessus des filtres et du calendrier à la taille maximale possible par défaut ».
- **Ce qui est retenu** :
  - le panneau des filtres (`FilterSheet`) et celui des demandes d'un jour du calendrier (`OfferCalendarScreen`) ont une hauteur fixe : tout l'écran sous la barre d'état, moins une bande du fond assombri (`Spacing.four`), qui reste visible et ferme le panneau au toucher ;
  - la hauteur vient d'un seul endroit, `useSheetHeight()` (`mobile/src/shared/hooks/`) ;
  - le contenu défile dans la place libre ; la barre « Effacer » / « Appliquer » des filtres reste en bas (le défilement des filtres prend `flex: 1`, aussi sur la page des filtres).
- **Remplace** : la hauteur au plus de 80 % (filtres) et de 70 % (calendrier).
- **Portée** : les panneaux des filtres et du calendrier (app).
- **Tranché par** : humain (taille maximale par défaut) ; session (la bande du fond gardée en haut)
