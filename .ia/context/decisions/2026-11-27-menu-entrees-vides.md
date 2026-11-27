# `2026-11-27` — « Dernières modifications » et « Membres » toujours au menu, même vides

Remplace en partie : 2026-11-27-menu-dernieres-modifications.md (l'entrée seulement avec des lignes)

- **Type** : décision
- **Question posée** : « les menu items dernières modifications et membres doivent être affichés même vides ».
- **Ce qui est retenu** : sur une notification de partenaire, « Dernières modifications » est toujours au menu, même sans modification enregistrée ; « Membres » l'était déjà. Chaque entrée du menu porte `empty`, le texte de son panneau quand il n'a rien à montrer (`null` s'il n'en a pas) : « Aucune modification enregistrée. » (`partner::partner.notify.changes.none`) pour `lines`. L'app montre l'entrée `lines` sans condition et, sans lignes, ce texte centré. « Membres » sans compte dit « Aucun membre » (`members_label`).
- **Motif** : le menu d'une notification de partenaire reste le même d'une notification à l'autre.
- **Portée** : web : `NotificationResource` (`empty`), écouteur Partner, langues ; mobile : type `NotificationMenuEntry`, `NotificationDetailScreen`.
- **Tranché par** : humain
