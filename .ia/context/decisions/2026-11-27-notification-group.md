# `2026-11-27` — Une notification dit si elle vient du fil d'un groupe (`group`)

- **Type** : décision
- **Question posée** : « ajouter la propriété group à la notification resource qui est à true pour les notifications de type partenaire ».
- **Ce qui est retenu** : `NotificationResource` sert `group` (booléen), sur la carte comme sur la page : vrai quand la notification appartient au fil d'un groupe inscrit (`NotifiableGroup`, fil `partner:group:5`), faux sinon. `NotificationThreads::isGroup()` le calcule à partir des préfixes des groupes inscrits.
- **Motif** : le module Notification ne connaît pas les partenaires ; il connaît les groupes. Le partenaire est aujourd'hui le seul groupe, donc `group` est vrai exactement pour ses notifications, et un futur groupe en profitera sans changement.
- **Portée** : API des notifications (web) ; type `Notification` du mobile (`group?: boolean`), pas encore utilisé par l'app.
- **Tranché par** : humain
