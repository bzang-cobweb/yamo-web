# `2026-11-26` — « Voir plus » ouvre les lignes dans un panneau

Complète : 2026-11-26-booking-summary.md, 2026-11-26-partenaire-dernieres-modifications.md, 2026-11-26-modifications-date-auteur.md

- **Type** : décision
- **Question posée** : « si on a vraiment beaucoup de choses modifiés, comment régler l'affichage si l'affichage prend toute la place quand on clique sur voir plus ? » puis « oui fais le panneau pour les deux ».
- **Ce qui est retenu** : sur la page d'une notification, les deux premières lignes restent visibles ; « Voir plus » ne déplie plus sur place, il ouvre un `BottomSheet` qui fait défiler toutes les lignes. Même composant (`SummaryLines`) pour les demandes et pour les partenaires.
- **Titre du panneau** : servi par le serveur dans `lines_title` — « Modifications » pour un partenaire, « Détails de la demande » pour une demande ; à défaut, « Détails » côté app.
- **Corollaires** : la clé « Voir moins » est retirée ; toucher un lien dans le panneau (auteur, membre) le ferme avant d'ouvrir la page.
- **Motif** : une longue liste de modifications (description comprise) ne repousse plus la conversation hors de l'écran ; la page garde sa forme quelle que soit la quantité de lignes.
- **Tranché par** : humain
