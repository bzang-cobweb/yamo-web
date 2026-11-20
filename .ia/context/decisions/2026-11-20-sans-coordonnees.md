# Une notification ne sert plus les coordonnées de qui agit

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Remplace** : 2026-11-20-coordonnees-notification.md

## Décision

`NotificationResource` n'a plus `withContact()` ; `ActorData` ne sert plus `email` ni
`phone`, nulle part (liste, page d'une notification, calendrier d'une offre). Dans l'app,
le type `NotificationActor` perd ces champs, et le composant `NotificationActor`, qui
n'était plus utilisé, est supprimé.

## Motif

Demande de l'humain du 2026-11-20 : « retire aussi le contact de NotificationResource ».
Depuis 2026-11-20-suivi-conversation, l'app ne les affichait plus : l'API servait encore
l'e-mail et le téléphone d'un autre membre sans usage. Ne pas les servir réduit les
données personnelles exposées.

## Portée

Toutes les réponses qui décrivent qui agit dans une notification ou une carte de demande.

## Corollaires

- Joindre l'autre partie passe par sa page de membre (le nom et la photo dans la conversation).
