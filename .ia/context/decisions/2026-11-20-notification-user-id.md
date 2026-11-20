# Qui agit dans une notification : colonne `user_id`

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« yes add column but name it user_id »), sur la recommandation d'une colonne simple plutôt que `notifier_type` / `notifier_id`
- **Complète** : 2026-11-19-carte-notification.md

## Décision

- **La colonne** : la table `notifications` porte `user_id`, liée aux comptes, vide sans personne qui agit et vidée quand le compte est supprimé. `actor_id` quitte les données JSON.
- **L'écriture** : le canal `database` du module Notification remplit `user_id` quand la notification implémente `HasNotificationUser`. Les notifications de demande le font.
- **La migration** : elle a déplacé l'`actor_id` des notifications existantes vers `user_id`.

## Motif

Une colonne se lie aux comptes, s'indexe et se cherche. Le JSON ne garde pas de lien vers le compte supprimé.

## Corollaires

- Une relation polymorphe (`notifier_type` / `notifier_id`) ne serait utile que pour des notifications envoyées par autre chose qu'un membre.
