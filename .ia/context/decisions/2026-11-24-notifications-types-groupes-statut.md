# `2026-11-24` — « Notifications » : les types groupés, un seul filtre « Statut » selon le type

Remplace : 2026-11-24-partenaire-notification-par-statut.md (seulement le filtre « Statut du partenaire »)

- **Type** : décision de conception
- **Question posée** : « regrouper tous les listeners par type (partenaire, réservation) et afficher les statuts selon le type ».
- **Réponse** (choix de l'humain) :
  - « listeners » désigne les options du filtre « Type » ;
  - un seul filtre « Statut », dont les options suivent le type choisi.
- **Ce qui est retenu** :
  - Une option de liste peut avoir un `group` : la valeur de l'option qui la regroupe (`Field::options()`, servi par l'API).
    - Dans l'app, l'option du groupe est en gras et ses options sont en retrait dessous.
    - Exemple : « Demande de logement », puis « Nouvelle demande », « Demande acceptée »… ; ensuite « Partenaire », puis « Partenaire en validation »…
  - `NotificationFilter::types()` donne le groupe de chaque type.
  - `NotificationFilter::statuses()` donne les statuts du module : valeur => libellé, et le groupe dont chaque statut dépend (`parent`).
  - Le module Notification sert un seul champ `status` (« Statut ») :
    - juste après « Type », visible dès qu'un type est choisi ;
    - dépendant de « Type » (`depends_on`).
  - Chaque module applique les valeurs de statut qui sont les siennes :
    - demande : son statut réel, avec les mêmes valeurs qu'avant (`pending`, `accepted`…) ;
    - partenaire : `partner_<statut>`, c'est-à-dire la classe de la notification.
  - Le filtre `partner_status` disparaît. Le statut d'une demande quitte les champs du module Offer pour ce filtre commun.
  - Dans l'app, une liste dépendante propose les options dont le parent est la valeur choisie ou le groupe de cette valeur (`dependentOptions`, partagé par les formulaires et les filtres). Changer le type vide le statut.
- **Motif** : la demande. Un seul endroit pour le statut, quel que soit le type.
- **Portée** :
  - web : Form (`Field`), Notification (contrat, `NotificationFilters`, traductions), Offer et Partner (filtres), et les tests ;
  - app : types de formulaire, `values.ts`, `DynamicForm`, `FilterScreen`, `OptionsModal`.
- **Corollaires** :
  - Chaque option servie par l'API porte maintenant `group` (`null` le plus souvent).
  - Une valeur de statut doit être unique entre les modules.
- **Tranché par** : humain (sens de « listeners », un seul filtre Statut) ; session (gras et retrait, valeurs `partner_<statut>`)
