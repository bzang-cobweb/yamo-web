Remplacée par : 2026-11-24-notifications-fil-de-groupe.md (une notification par action, sans copie)

# La réponse du propriétaire dans son propre fil

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-suivi-demande.md

## Décision

Quand le propriétaire accepte ou refuse une demande, il reçoit lui aussi une
notification de cette étape (`BookingRequested`, `own: true`) :

- dans l'onglet « Notifications » seulement : ni e-mail ni SMS ;
- enregistrée déjà lue : le badge ne compte pas sa propre action ;
- sans qui agit (`user_id` nul) : la carte montre son texte entier, « Vous avez
  accepté la demande de Marie Curie pour « Studio » (du … au …). » ;
- dans le fil de la demande : la carte du propriétaire porte sa réponse et remonte
  en tête de la liste ;
- absente du suivi (page de la notification) : l'étape y figure déjà, par la
  notification du membre.

## Motif

Réponse de l'humain du 2026-11-20 (« Yes, do it ») à la question : la carte du
propriétaire doit-elle montrer sa propre réponse et remonter en tête ? Sans cela, une
demande acceptée restait chez lui à l'étape « demande reçue », à sa place d'origine.

## Portée

Les réponses du propriétaire (acceptation, refus, y compris les refus automatiques
des demandes qui chevauchent une demande acceptée). Pas l'annulation par le membre ni
sa demande : le membre ne reçoit pas de trace de ses propres actions.

## Corollaires

- Contrat `Modules\Notification\Contracts\SentAsRead` : le canal `database` remplit
  `read_at` à l'envoi.
- `NotificationThreads::steps()` écarte les notifications `data.own`.
- Les demandes déjà répondues avant ce changement n'ont pas cette trace.
