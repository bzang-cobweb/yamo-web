# Un filtre à une seule option n'est jamais verrouillé

- **Type** : correctif de conception
- **Date** : 2026-11-20
- **Origine** : l'humain (« quand on clique depuis la card mes logements sur le x demandes en attente, le filtre type demande de logement doit être sélectionné »)

## Constat

Le filtre « Type » des notifications n'a qu'une option (« Demande de logement »). Une liste à une option est verrouillée : sa valeur par défaut devient cette option (décision 2026-11-20-pays-unique-identifiant-double). L'app compare chaque valeur de filtre à la valeur par défaut pour reconnaître « Tous » : `type=booking`, posé par le compteur, était pris pour « Tous » et retiré. Il en allait de même pour un filtre « Logement » ou « Membre » à une seule option.

## Décision

- Un champ servi comme **filtre** de liste (`Field::asFilter()`, appliqué là où les filtres sont servis : `ListingResource`, `NotificationPageResource`) n'est **jamais verrouillé** et n'a pas de valeur par défaut imposée. « Tous » reste possible ; une valeur choisie, même la seule option, filtre.
- Les formulaires gardent le verrouillage d'une option unique.
