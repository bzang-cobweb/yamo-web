# RETEX — avis sur les offres (2026-11-19)

## Ce qui s'est produit deux fois

- **Tests qui figent la forme exacte d'un item de liste** : `ListingApiTest`, `UserAddressApiTest` et `OfferBrowseApiTest` ont tous cassé quand `ListItem` a gagné deux champs.
  - Règle candidate (projet) : n'affirmer la liste exacte des clés d'un item qu'à **un** endroit (`ListingApiTest`) ; ailleurs, affirmer les valeurs utiles.
- **Super administrateur et `HasBeforeAbility`** : deux policies ont dû l'exclure pour certaines règles (`review`, et create/update des avis).
  - Règle candidate (projet) : une règle métier qui vaut pour tout compte (« pas sur sa propre offre ») se déclare hors du contournement, dans la policy elle-même.
- **État partagé entre deux requêtes dans un même test** : déjà vu avec les gardes Sanctum (`forgetGuards`) ; cette fois, les en-têtes (`withToken` gardé d'un appel à l'autre, d'où `flushHeaders`).
  - Règle candidate : un helper de test qui alterne les comptes réinitialise gardes **et** en-têtes.

## Règle ré-implémentée / domiciliée

- **Retrait décrit par le serveur** (libellé, confirmation, envoi) : réutilisé tel quel depuis le retrait d'un item de liste, pour retirer son avis. Pas de nouvelle implémentation.
- **Formulaire générique** : le champ `rating` est domicilié dans le module Form (type, validation, rendu dans le back-office et dans l'app), réutilisable par tout domaine.

## Divers

- Le collage de texte dans le simulateur abîme les accents. Pour les essais au simulateur, taper sans accents.
