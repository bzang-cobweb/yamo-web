# `2026-11-19` — Contrat « règles et informations pratiques d'un logement », validé

- **Type** : décision
- **Question posée** : « add informations textarea rte (must be option in textarea field type) … is there a better way to do this ? where to put this ? » ; j'ai recommandé des champs structurés plus un texte libre (choisi : « Structured + free text »), puis le contrat `specs/2026-11-19-regles-informations.md` avec trois défauts.
- **Réponse** : « Approved » (défauts retenus).
- **Ce qui est retenu** :
  - attributs facultatifs du logement : arrivée à partir de, départ avant (heures, meublé seulement), âge minimum, documents demandés (choix multiples), animaux / fumeurs / fêtes (Oui / Non), autres informations (texte traduisible, 2000 caractères) ;
  - pas d'éditeur de texte riche, pas de dépendance ajoutée ;
  - étape « Règles et informations » en 4e position du formulaire de l'app ; onglet « Règles » au back-office ;
  - fiche : section repliable « Règles du logement » après « Caractéristiques » (lignes renseignées, puis le texte) ; ces champs quittent « Caractéristiques » ;
  - pas de filtre de l'accueil sur ces champs pour l'instant.
- **Motif** : des informations validées, traduites, affichées de la même façon sur toutes les offres et filtrables plus tard ; le texte riche aurait demandé deux bibliothèques et un nettoyage HTML.
- **Portée** : `Domain`, `RentalDomain`, `OfferDetailResource`, formulaire Filament ; app (`OfferDetailScreen`).
- **Corollaires** : l'option « texte riche » du type textarea n'est pas créée.
- **Tranché par** : humain
