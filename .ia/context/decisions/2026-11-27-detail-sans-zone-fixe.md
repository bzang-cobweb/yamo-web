# `2026-11-27` — Page d'une notification : plus de zone fixe en haut

Remplace en partie : 2026-11-20-suivi-conversation.md (la zone fixe), 2026-11-26-lignes-en-panneau.md (« Voir plus »), 2026-11-21-voir-plus-apres-deux-lignes.md

- **Type** : décision
- **Question posée** : « enlever la zone fixe qui contenait le heading et autres ».
- **Ce qui est retenu** : la page d'une notification n'a plus de zone fixe sous la barre du haut ; elle ne montre que le fil (ou le message et la date) et le bouton du bas. Ce que la zone portait passe dans le panneau « Info » du menu ⋮, dans cet ordre : la pastille du statut, le titre de l'en-tête (en lien vers sa page s'il en a une) et son sous-titre — à défaut d'en-tête, le texte de la notification et ses liens —, puis le titre des lignes servi par le module (`lines_title`) et toutes les lignes.
- **Motif** : le titre de l'en-tête est déjà dans la barre du haut (2026-11-27-page-titre-entete) et tout le reste est dans « Info » : la zone répétait l'information et prenait la place du fil.
- **Portée** : mobile, `NotificationDetailScreen`. `BookingSummary` et le « Voir plus » de `SummaryLines` disparaissent (seul `SummaryLineRow` reste) ; les clés `notifications.more` et `notifications.details` sont retirées.
- **Corollaires** : un lien suivi depuis « Info » ferme d'abord le panneau.
- **Tranché par** : humain
