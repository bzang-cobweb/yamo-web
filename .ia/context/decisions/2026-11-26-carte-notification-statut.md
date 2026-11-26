# `2026-11-26` — La pastille du statut revient sur la carte d'une notification

Remplace en partie : 2026-11-22-carte-notification-type-auteur-message-date.md (« plus de statut » sur la carte)

- **Type** : décision
- **Question posée** : « pourquoi il n'y a plus les statuts dans les cards notifications de booking et partenaire ? » → « la remettre ».
- **Ce qui est retenu** :
  - la carte d'une notification montre de nouveau la pastille du statut (`tag`, déjà servie par l'API), en haut à droite, sur la ligne du type en gras, comme le voulait 2026-11-20-statut-en-haut-a-droite ;
  - le statut réel d'une demande, celui qu'annonce une notification de partenaire ; rien pour un message ;
  - le composant `NotificationType` (le type et la pastille sur une ligne), resté inutilisé, sert de nouveau sur la carte ;
  - le reste de la carte ne change pas : l'expéditeur, le texte et le message (tronqués, 2026-11-26-cartes-notifications-tronquees), la date.
- **Motif** : voir l'état d'une demande ou d'un partenaire sans ouvrir la notification.
- **Portée** : la liste des notifications (app).
- **Tranché par** : humain
