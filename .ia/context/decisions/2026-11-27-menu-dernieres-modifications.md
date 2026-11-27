# `2026-11-27` — Partenaire : « Dernières modifications » dans le menu ⋮

Remplace en partie : 2026-11-27-detail-sans-zone-fixe.md (les lignes d'un partenaire dans « Info »)

- **Type** : décision
- **Question posée** : « la notification d'un partner ajoute dans le menu un item dernières modifications s'il y a des lines, qui ouvre dans un panneau à la hauteur maximale les lines ».
- **Ce qui est retenu** : quand le partenaire a des dernières modifications (`lines`), `AddPartnerToNotificationThread` ajoute au menu, après « Info », l'entrée `{key: 'lines', label: 'Dernières modifications', link: null}` (`partner::partner.notify.changes.menu`). Dans l'app, une entrée `lines` sans lien ouvre les `lines` servies dans un panneau à taille maximale, titré du libellé de l'entrée ; sans `lines`, l'entrée n'est pas montrée. « Info » ne montre plus que les lignes d'une demande (`booking.lines`, titrées `lines_title`).
- **Motif** : les modifications ont leur entrée, nommée ; « Info » reste la fiche de la notification.
- **Portée** : module Partner (écouteur, clé de langue `changes.menu` ; `changes.title` et `lines_title` du partenaire retirés) ; mobile `NotificationDetailScreen`. L'entrée `lines` est générique : un autre module qui sert `lines` peut l'ajouter.
- **Tranché par** : humain
