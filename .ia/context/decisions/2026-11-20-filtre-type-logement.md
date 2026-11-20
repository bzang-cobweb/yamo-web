# Filtre « Type de logement » dans la liste des notifications

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« ajouter un filtre pour le type de logement (location longue durée => visite, location meublé => séjour) affiché si le filtre type concerne booking », « dans notifications list filters »)
- **Complète** : 2026-11-20-filtres-notifications.md

## Décision

- **Le filtre** : « Type de logement », dans les filtres de la liste des notifications, avec deux choix :
  - « Location meublée » (un séjour) ;
  - « Location longue durée » (une visite).
- **Quand il apparaît** : seulement quand le filtre « Type » vaut un type de demande, comme « Logement ».
- **Ce qu'il garde** : les notifications dont l'offre se demande ainsi, selon le domaine (`Domain::bookingType()` : un meublé se réserve, un non meublé se visite). La vérification se fait à la lecture, sur l'offre.
- **Les libellés** : chaque domaine nomme ses types de demande (`Domain::bookingTypeLabels()`). La location donne « Location meublée » et « Location longue durée ».
