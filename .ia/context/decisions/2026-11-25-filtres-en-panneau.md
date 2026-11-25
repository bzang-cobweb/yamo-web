# `2026-11-25` — Les filtres de la recherche dans un panneau qui monte du bas

- **Type** : décision
- **Questions posées** :
  - « ouvrir les filtres de la recherche comme on ouvre la liste des demandes du jour dans le calendrier » ;
  - « mettre une bordure après la ligne Filtres ; ouvrir tous les filtres de la même manière » ;
  - « remplacer fermer par une icône » ;
  - « arrondir les bords du haut ».
- **Ce qui est retenu** :
  - Dans toutes les listes, « Filtres » n'ouvre plus un écran à part : la recherche, les listes du profil (« Mes logements », favoris, réservations, demandes, « Mes partenaires »…) et les notifications. Il ouvre un panneau qui monte du bas, par-dessus la liste, comme les demandes d'un jour du calendrier :
    - un fond assombri qui ferme au toucher ;
    - des coins arrondis en haut (20 points), visibles sur le fond assombri qui passe derrière tout le panneau ;
    - le titre « Filtres » et une croix pour fermer (lue « Fermer »), sur une ligne bordée en bas ;
    - les champs qui défilent, au plus 80 % de la hauteur de l'écran (pas de la fenêtre, plus haute dans un écran présenté) ;
    - en bas, « Effacer les filtres » et « Appliquer les filtres ».
  - « Appliquer » et « Effacer » ferment le panneau. La croix et le fond le ferment sans rien changer.
  - Les champs repartent des filtres en cours à chaque ouverture.
  - Les champs et leur barre sont communs à l'écran des filtres et au panneau (`FilterForm`).
  - L'écran des filtres (`/filters/[list]`) reste, pour un lien direct.
- **Tranché par** : humain (le panneau, partout, la bordure, la croix) ; session (80 % de l'écran, l'écran gardé pour un lien direct)
