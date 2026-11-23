# Ouvrir une demande marque lues toutes ses notifications adressées au lecteur

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« si un utilisateur ouvre le détail d'une demande, toutes les notifications qui lui sont adressées qui sont dans la demande doivent être marquées comme lues »)
- **Complète** : 2026-11-20-suivi-demande

## Décision

Ouvrir la page d'une demande marque lues toutes les notifications de son fil adressées au lecteur, quelle que soit celle qui sert à ouvrir la page. Jusqu'ici, si cette notification était déjà lue (sa propre copie, ouverte depuis « Mes réservations »), l'app ne marquait rien, et les autres étapes non lues le restaient.

La page d'une notification se dit maintenant non lue tant qu'une notification de son fil, adressée au lecteur, l'est encore. L'app la marque alors lue, et le serveur marque tout le fil (règle existante).

## Portée

`NotificationController::show` (`withRead` d'après le fil). Rien ne change côté mobile.
