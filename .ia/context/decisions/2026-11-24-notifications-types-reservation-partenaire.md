# `2026-11-24` — « Notifications » : deux types, « Réservation » et « Partenaire »

Remplace : 2026-11-24-notifications-types-groupes-statut.md (le regroupement des types) ; 2026-11-22-filtre-type-par-action.md

- **Type** : décision de conception
- **Question posée** : « le type affiche uniquement demande de logement renommer en réservation et partenaire. le filtre statut s'affiche seulement si le type est sélectionné ».
- **Ce qui est retenu** :
  - Le filtre « Type » ne propose que deux options, chacune pour toutes les notifications de son type :
    - « Réservation » (`booking`), toutes les actions d'une demande ;
    - « Partenaire » (`partner`), tous les statuts.
  - Le libellé « Réservation » vient de `offer::booking.notify.filter_type` (Booking, Buchung), et non plus du libellé de demande du domaine.
  - Plus d'option par action (« Nouvelle demande »…) ni par statut de partenaire.
  - `NotificationFilter::types()` ne donne plus de groupe.
  - « Statut » ne s'affiche que si un type est choisi. Ses options sont celles du type : les statuts d'une demande, ou ceux d'un partenaire (`parent`).
  - « Logement » et « Type de location » ne s'affichent qu'avec « Réservation ».
- **Motif** : la demande.
- **Corollaires** :
  - Les classes par action et par statut restent : elles donnent le titre et la pastille des cartes, et elles servent au filtre « Statut » d'un partenaire.
  - La possibilité qu'a une option de liste d'avoir un `group` (gras, puis options en retrait) reste dans `Field` et dans l'app. Elle ne sert plus nulle part pour l'instant.
- **Tranché par** : humain
