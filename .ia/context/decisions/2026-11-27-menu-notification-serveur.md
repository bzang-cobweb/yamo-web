# `2026-11-27` — Le menu ⋮ de la page d'une notification est servi par le serveur

Remplace en partie : 2026-11-27-notification-menu-info.md (le menu écrit dans l'app)

- **Type** : décision
- **Question posée** : « comment est passé le menu dropdown à l'app ? » — réponse : écrit en dur dans l'app ; l'humain a choisi « le serveur ».
- **Ce qui est retenu** : `NotificationResource` sert `menu` sur la page d'une notification (pas sur les cartes) : une liste ordonnée d'entrées `{key, label, link}`. Le module Notification y met `info` (libellé `notification::notification.menu.info`, `link` nul) ; le module du fil peut en ajouter par `NotificationThreadSerializing`. L'app montre ce qu'elle sait ouvrir : une entrée avec `link` ouvre son écran (ou un formulaire du profil dans un panneau) ; `info` sans `link` ouvre le panneau « Info », titré du libellé servi ; une autre entrée sans lien est ignorée. Sans entrée, pas de bouton ⋮.
- **Motif** : comme les actions des cartes de liste, le serveur décide de ce qu'on peut faire ; un module ajoute une entrée sans livrer l'app.
- **Portée** : `NotificationResource`, fichiers de langue `notification.php` (clé `menu.info`, retirée de `mobile.php`) ; mobile : type `NotificationMenuEntry`, `NotificationDetailScreen`. Le libellé d'accessibilité du bouton (`notifications.actions`) reste un texte de l'app.
- **Tranché par** : humain
