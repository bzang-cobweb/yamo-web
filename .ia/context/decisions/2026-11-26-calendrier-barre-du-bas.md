# `2026-11-26` — Calendrier : ce qui vient sous les demandes d'un jour, dans la barre fixe du bas

- **Type** : décision
- **Question posée** : « placer tout ce qu'il y a sous les demandes dans la barre du bas ».
- **Ce qui est retenu** :
  - dans le panneau d'un jour du calendrier, la note (« Ce jour a un séjour accepté… ») et l'action (« Bloquer ce jour » ou « Débloquer ») quittent la fin de la liste : elles sont dans une barre fixe en bas, bordée en haut comme celle des filtres ; les demandes défilent au-dessus ;
  - la barre n'apparaît que s'il y a une note ou une action (pas pour un jour passé), et pas pendant le chargement ;
  - le serveur ne change pas : `OfferCalendar::day()` sert toujours `note` et `action`.
- **Motif** : le panneau s'ouvre à sa taille maximale (2026-11-26-panneaux-taille-maximale) ; en fin de liste, l'action flottait au milieu du panneau un jour avec peu de demandes, et défilait hors de vue un jour qui en a beaucoup.
- **Portée** : le panneau d'un jour du calendrier (app).
- **Tranché par** : humain
