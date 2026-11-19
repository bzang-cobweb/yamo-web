# RETEX — offres en vedette et favoris (2026-11-19)

## Ce qui s'est produit deux fois

- **Une limite introduite par l'implémentation a fait perdre des données**, et seule la QA l'a vue : les vedettes au-delà de 20 étaient exclues partout.
  - Règle candidate : toute limite technique (`limit`, plafond) qui exclut des enregistrements d'un autre chemin doit avoir un test « au-delà de la limite ».
- **Un point d'API ou un lien servi par le serveur était validé trop largement dans l'app** (préfixe `/v1/`, chemin quelconque). Une validation large avait déjà dû être resserrée à propos des envois décrits (`profileSubmission`).
  - Règle candidate (projet) : l'app valide chaque URL servie contre la **forme exacte** de son usage (`/v1/<entité>/<id>/favorite`, `/<entité>/<id>`), jamais un simple préfixe.
- **Les tests qui figent la forme d'un item de liste** ont encore cassé (ListingApiTest, UserAddressApiTest, OfferBrowseApiTest), pour la troisième fois.

## Règle ré-implémentée / domiciliée

- **Les épinglés en tête d'une liste paginée par curseur** sont domiciliés dans `Listing` (`pinned()`, `pinnedLimit()`, `pinnedItems()`). La sous-classe exclut exactement les mêmes ids.
- **Le cœur** est un composant générique (`FavoriteHeart`, `ItemPicture`), piloté par le champ `favorite` de `ListItem`, sans rien de propre aux offres.
