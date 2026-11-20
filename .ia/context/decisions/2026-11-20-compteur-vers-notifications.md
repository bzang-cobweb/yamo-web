# « x demandes en attente » de Mes logements ouvre les notifications

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« dans mes logements card quand je clique sur x demande en attente je suis redirigé vers la page notifications avec filtre type demande de logement en attente, logement qui est mon logement »)
- **Remplace en partie** : 2026-11-19-contrat-reservations-logements-profil.md (le compteur ouvrait « Demandes reçues » filtrée)

## Décision

- **Où il mène** : le compteur d'une carte de « Mes logements » ouvre l'onglet « Notifications », filtré sur :
  - le type « Demande de logement en attente » ;
  - ce logement.
- **Comment** : un compteur de liste peut désormais nommer un écran de l'app (`ListItem::counter(…, link: '/notifications')`). Sa liste (`notifications`) nomme alors les filtres à préremplir.

## Corollaires

- **Ce qui est compté** : le compteur compte les demandes **encore** en attente. Le filtre « Type » montre toutes les demandes reçues pour ce logement, y compris celles acceptées depuis, car la pastille d'une notification suit son type, pas le statut actuel. Les deux nombres peuvent donc différer.
