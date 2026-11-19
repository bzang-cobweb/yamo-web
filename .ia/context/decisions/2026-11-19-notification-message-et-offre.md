# Notification d'une demande : le message saisi et l'identifiant de l'offre

- **Type** : décision de conception
- **Date** : 2026-11-19
- **Décidé par** : l'humain (« on n'a pas besoin de filtrer les notifications ; il faudrait qu'on retrouve le message de l'utilisateur dans la demande s'il a saisi un message ; rajouter l'identifiant de l'offre dans les données des notifications »)
- **Complète** : 2026-11-19-notification-demande-statut-lien-offre.md

## Décision

- **Message** : les données d'une notification de demande gardent le message saisi : celui du membre pour une nouvelle demande, le motif pour un refus, `null` sinon. L'API l'expose (`message`) et l'app l'affiche en citation sous le texte, seulement s'il existe.
- **Offre** : les données gardent l'identifiant de l'offre (`offer_id`), pour qu'une notification puisse être rattachée à son offre plus tard. L'API des notifications ne l'expose pas : l'app ouvre l'offre avec le lien « Voir l'annonce ».
- **Filtres** : les notifications n'ont pas de filtres.

## Motif

Le propriétaire lit le message du membre sans ouvrir la demande. Garder l'identifiant de l'offre plutôt que seulement des textes figés permet, plus tard, de retrouver ou de recalculer ce qui manque.

## Portée

Les notifications créées à partir de maintenant ; les anciennes n'ont ni message ni offre.

## Corollaires

- Une nouvelle notification peut joindre un `message` à ses données sans toucher à l'app.
