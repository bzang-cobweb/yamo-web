# Un logement avec une demande en cours ne change pas de statut et ne se retire pas

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« la règle pour changer le statut d'une offre ne fonctionne pas… », puis : « bloqué tant qu'il reste une demande en attente ou acceptée », et « Retirer » bloqué aussi)

## Décision

Tant qu'un logement a une **demande en cours**, son propriétaire ne peut ni changer son statut (vers brouillon ou archivé), ni le retirer. Une demande en cours, c'est :
- une demande en attente et pas expirée ;
- ou une demande acceptée dont le dernier jour n'est pas passé.

Ne bloquent pas : les demandes terminées, refusées, annulées ou expirées.

- **Changer le statut** vers brouillon ou archivé : refusé à l'enregistrement, sur le champ « Statut » : « Ce logement a une demande en attente ou acceptée : son statut ne peut pas changer avant qu'elle soit réglée ou terminée. » Le reste du formulaire s'enregistre si le statut ne change pas. Revenir à « Publiée » reste toujours possible.
- **Retirer** : 403 « … il ne peut pas être retiré avant qu'elle soit réglée ou terminée. ». Sur la fiche, « Retirer » n'apparaît pas tant que c'est verrouillé.

## Portée

- App : scope `Booking::open`, `Offer::hasOpenBookings`, une règle sur le champ `status` de `OfferForm` (API) et `OfferPolicy::delete`.
- Le back-office n'est pas concerné : un administrateur garde la main.

## Corollaires

Avant cette règle, un logement a pu être passé en brouillon avec des demandes en cours : en local, l'offre 2 (« Appartement 3 pièces à Bonapriso »). Elle reste ainsi, mais elle peut revenir à « Publiée » à tout moment.
