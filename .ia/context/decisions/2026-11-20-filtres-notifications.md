# Recherche et filtres de la liste des notifications

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« add filters in notifications list: filter by offer, and offer statut » ; « add search in notifications list (search on user name and offer name) » ; « ajouter un filtre sur le user » ; « les filtres logement et user doivent avoir une recherche » ; « filtre statut concerne le statut de la demande pas du logement » ; puis « add filter type… show logement if type is about booking and remove statut filter », « filters list show max 10 records », « add all option to all filters list »)

## Décision

- **La recherche** : elle porte sur le nom de qui agit (chaque mot, dans le prénom ou le nom) et sur le titre du logement, dans toutes les langues, sans tenir compte de la casse (`ContainsSearch`).
- **Les filtres** :
  - « Type », parmi les types de ses notifications, nommés par les modules (« Demande de logement en attente », « … acceptée », « … refusée », « … annulée ») ;
  - « Membre » (qui agit, colonne `user_id`), parmi les membres que nomment ses notifications, porté par le module Notification lui-même ;
  - « Logement », parmi les logements que nomment ses notifications, affiché seulement pour un type de demande (`visibleWhen` accepte une liste de valeurs).

  Le filtre « Statut » est retiré.

- **Dans les listes déroulantes de tous les filtres** (écran générique de l'app) :
  - « Tous » vient en tête, et reste choisi tant qu'on n'a rien choisi ;
  - une liste à rechercher (« Membre », « Logement ») montre au plus 10 options, et la recherche trouve les autres ;
  - un filtre masqué par sa condition n'est pas appliqué.

- **L'API** : `GET /v1/notifications?search=…&filter[user]=…&filter[offer]=…&filter[type]=…` sert avec chaque page les champs des filtres (`filters`). L'app les ouvre dans l'écran générique des filtres (`/filters/notifications`).
- **Les modules** : le module Notification ne connaît pas les offres. Le module Offer inscrit ses filtres et sa recherche (`NotificationFilter`, `NotificationFilters`).
- **L'app** :
  - la liste des notifications utilise le composant `List` (recherche, filtres, défilement infini) ;
  - le lien « Filtres » est partagé (`FilterLink`) ;
  - l'attente de frappe est partagée (`useDebounced`).

## Portée

Toutes les notifications du compte. Avec un seul logement, le filtre « Logement » est présélectionné, comme dans les autres listes.
