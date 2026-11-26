# `2026-11-26` — Depuis la page d'une demande, ses formulaires s'ouvrent dans un panneau

Complète : 2026-11-21-detail-reservation-modifier.md, 2026-11-21-annuler-depuis-le-detail.md, 2026-11-26-lignes-en-panneau.md

- **Type** : décision
- **Question posée** : « depuis ma réservation, ouvrir les formulaires annuler et modifier une demande dans le pannel comme voir plus ».
- **Ce qui est retenu** : sur la page d'une notification, un bouton du bas dont le lien est un formulaire du profil (`/profile/<formulaire>?record=<id>`) ouvre ce formulaire dans un `BottomSheet` au lieu d'un écran. Le panneau a pour titre le titre de navigation du formulaire (« Modifier la demande », « Annuler la demande »), sinon son titre. Les champs défilent et le bouton d'envoi est fixé en bas du panneau. Cela vaut aussi pour « Inviter à donner un avis » du propriétaire. Un autre lien (« Laisser un avis », `/offers/5/review`) ouvre toujours son écran.
- **Envoyé** : le panneau se ferme, le message du serveur s'affiche dans le bandeau, la demande est relue (dates, statut, boutons). La croix ou le fond ferment le panneau sans rien envoyer.
- **Corollaires** : le contenu d'un formulaire du profil est un composant sans cadre (`ProfileForm`), posé soit dans l'écran (`ProfileFormScreen`), soit dans le panneau (`ProfileFormSheet`) (skill `bza-dev:composition`). Le serveur ne change pas : il sert toujours les mêmes liens.
- **Motif** : la demande reste visible derrière le formulaire, comme pour « Voir plus », et on revient à la page sans changer d'écran.
- **Tranché par** : humain
