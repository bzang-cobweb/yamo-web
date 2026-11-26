# `2026-11-26` — « Demande de séjour reçue » ; le type de demande aussi dans les messages de réussite

- **Type** : décision (complète 2026-11-26-titres-type-de-demande)
- **Question posée** : « demande de séjour reçue, demande de visite reçue, rajouter visite et séjour pour toutes les demandes (acceptée, refusée, annulée, …) ».
- **Ce qui est retenu** :
  - la nouvelle demande, chez le propriétaire, s'appelle « Demande de séjour reçue » / « Demande de visite reçue » (au lieu de « Nouvelle demande de … ») : la carte (`notify.types.received`), le sujet de l'e-mail (`notify.requested.subject`) et le titre de la page (`detail.received`, au lieu de « Demande reçue ») ; en « … received », « … erhalten » ;
  - les messages de réussite disent le type : « Demande de séjour annulée. » (annuler), « … refusée. » (refuser), « … acceptée. » (accepter), « … modifiée. » (modifier), « Demande de séjour envoyée. … » (réserver ; « Demande de visite envoyée. » l'était déjà) ;
  - `BookingNotification::requestLabel($type)` donne le `:request` partout.
- **Motif** : un séjour et une visite se distinguent dans chaque titre et chaque message d'une demande.
- **Portée** : notifications, page et formulaires d'une demande (fr, en, de).
- **Tranché par** : humain ; session (le titre de la page, les messages de réussite, en et de)
