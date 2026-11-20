# Recherche et filtres de la liste des notifications

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« add filters in notifications list: filter by offer, and offer statut » ; « add search in notifications list (search on user name and offer name) » ; « ajouter un filtre sur le user » ; « les filtres logement et user doivent avoir une recherche » ; « filtre statut concerne le statut de la demande pas du logement »)

## Décision

- **La recherche** : elle porte sur le nom de qui agit (chaque mot, dans le prénom ou le nom) et sur le titre du logement, dans toutes les langues, sans tenir compte de la casse (`ContainsSearch`).
- **Les filtres** :
  - « Membre » (qui agit, colonne `user_id`), parmi les membres que nomment ses notifications, porté par le module Notification lui-même ;
  - « Logement », parmi les logements que nomment ses notifications ;
  - « Statut » : le statut de la demande tel que la pastille le montre, figé à l'envoi (en attente, acceptée, refusée, annulée). Les notifications de demande gardent pour cela `booking_id` et `booking_status`.

  « Membre » et « Logement » ont une recherche dans leur liste d'options (`Field::searchable()`).

  Une notification sans membre, ou sans logement, ne passe pas le filtre correspondant.
- **L'API** : `GET /v1/notifications?search=…&filter[user]=…&filter[offer]=…&filter[status]=…` sert avec chaque page les champs des filtres (`filters`). L'app les ouvre dans l'écran générique des filtres (`/filters/notifications`).
- **Les modules** : le module Notification ne connaît pas les offres. Le module Offer inscrit ses filtres et sa recherche (`NotificationFilter`, `NotificationFilters`).
- **L'app** :
  - la liste des notifications utilise le composant `List` (recherche, filtres, défilement infini) ;
  - le lien « Filtres » est partagé (`FilterLink`) ;
  - l'attente de frappe est partagée (`useDebounced`).

## Portée

Toutes les notifications du compte. Avec un seul logement, le filtre « Logement » est présélectionné, comme dans les autres listes.
