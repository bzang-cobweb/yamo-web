# Fiche d'un logement : la description repliée, « Voir plus » / « Voir moins »

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« mettre un lien voir plus/moins sur la description d'une offre sur la page de détail d'une offre »)

## Décision

Sur la fiche d'un logement, une description de plus de 4 lignes s'affiche repliée à 4 lignes. Dessous, à droite, un lien « Voir plus » la déplie, puis « Voir moins » la replie, comme les lignes d'une demande. Une description de 4 lignes ou moins s'affiche entière, sans lien. En anglais : « Show more » / « Show less » ; en allemand : « Mehr anzeigen » / « Weniger anzeigen ».

## Portée

Mobile : `OfferDetailScreen` (composant `Description`, lignes comptées au premier affichage). Textes `offer.more` et `offer.less` servis par Laravel (module Offer, `mobile.php`).
