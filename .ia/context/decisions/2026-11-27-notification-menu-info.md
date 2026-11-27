# `2026-11-27` — Page d'une notification : un menu ⋮ avec « Info »

- **Type** : décision
- **Question posée** : « détail notification ajouter un dropdown 3 petits points à droite avec un menu info qui affiche le titre, le sous titre et les lignes s'il y en a », puis « mettre le titre de la notification comme titre de la page ».
- **Ce qui est retenu** :
  - à droite de la barre du haut de la page d'une notification, un menu « ⋮ » ; son entrée « Info » (en, de : « Info ») ouvre un panneau à sa hauteur, titré du titre de la notification (celui de la barre du haut, « Partenaire message ») ; dedans, le titre de l'en-tête en gras, son sous-titre, puis toutes les lignes (celles d'une demande ou celles d'un partenaire) s'il y en a ; un lien d'une ligne ferme le panneau et ouvre sa page ; toucher ailleurs ferme le menu ;
  - un seul menu « ⋮ » dans l'app : `DropdownMenu` (bouton rond et entrées flottantes), utilisé par les cartes des listes et par cette page ; `BackHeader` prend un emplacement à droite (`right`) ; la ligne libellé-valeur est un composant (`SummaryLineRow`), partagé par les lignes de la page, leur panneau « Voir plus » et « Info ».
- **Tranché par** : humain
