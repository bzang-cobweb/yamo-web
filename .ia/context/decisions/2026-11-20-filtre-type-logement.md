# Filtre « Type de logement » dans la liste des notifications

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« ajouter un filtre pour le type de logement (location longue durée => visite, location meublé => séjour) affiché si le filtre type concerne booking », « dans notifications list filters », puis « utiliser les labels offer type » → « catégorie de l'offre », puis « rename type de logement by catégorie »)
- **Complète** : 2026-11-20-filtres-notifications.md

## Décision

- **Le filtre** : « Catégorie » (d'abord nommé « Type de logement »), dans les filtres de la liste des notifications. Ses choix sont les **catégories** des offres que nomment ses notifications (« Location meublée », « Location longue durée », « Colocation »), avec le nom de la catégorie dans la langue du lecteur.
- **Ce qu'il garde** : les notifications dont l'offre est dans cette catégorie.
- **Quand il apparaît** : seulement quand le filtre « Type » vaut un type de demande, comme « Logement ».
- **Ce qui a été retiré** : la première version (séjour / visite, avec des libellés de domaine `bookingTypeLabels()`).
