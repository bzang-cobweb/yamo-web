# Suivi d'une demande : des coches sur ses propres étapes

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-suivi-conversation.md

## Décision

Dans la conversation de la page d'une notification, après la date de chaque étape du
lecteur, des coches disent ce qu'est devenue la notification envoyée à l'autre partie :

| État (`delivery`) | Quand | Coches |
|---|---|---|
| `sent` | l'autre ne s'est pas connecté depuis l'envoi (`last_seen_at`) | une grise |
| `delivered` | connecté depuis, notification non lue | deux grises |
| `read` | notification lue (`read_at`) | deux vertes |

L'API sert `delivery` sur chaque étape du lecteur, `null` sur celles de l'autre. Lire
une carte de demande marque lue tout son fil chez le lecteur (2026-11-20-suivi-demande) :
« lue » veut donc dire que l'autre a ouvert la demande depuis.

Aussi : dans la zone fixe, la pastille du statut vient après le texte, alignée à droite.

## Motif

Demande de l'humain du 2026-11-20 : « un simple check si l'utilisateur qui lit ne s'est
pas connecté depuis l'envoi de notre notification », « un double check s'il s'est
connecté … et que la notification n'est toujours pas lue », « un check vert si notre
notification est marquée comme lue ». Deux coches vertes plutôt qu'une : décidé seul,
pour la continuité avec les deux coches grises.

## Portée

La page d'une notification dans l'app.

## Corollaires

- L'autre partie voit si et quand on a lu sa notification : c'est voulu.
- `last_seen_at` n'est mis à jour qu'à intervalles : une connexion très récente peut
  encore afficher une seule coche.
