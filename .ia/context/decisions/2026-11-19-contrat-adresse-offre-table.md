Remplace : 2026-11-19-description-adresse-offre.md

# `2026-11-19` — Contrat « adresse d'une offre : table `offer_addresses` », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-adresse-offre-table.md` (demande : « enlever le champ address_description et faire une table OfferAddress qui référence une Address et contient en plus un champ indication »), avec deux points : retirer aussi `offers.address_id`, nommer le champ `indication`.
- **Réponse** : « Approved » (défauts retenus).
- **Ce qui est retenu** :
  - table `offer_addresses` : `offer_id` unique (supprimé avec l'offre), `address_id` (supprimé avec l'adresse), `indication` (500 caractères au plus, facultatif, non traduit) ;
  - `offers.address_id` et `offers.address_description` sont retirés ; une migration recopie les données existantes ;
  - `Offer::address` reste lisible, à travers `OfferAddress` ;
  - le champ du formulaire (app, back-office) et la clé de la fiche s'appellent `indication` ;
  - mêmes règles d'enregistrement : adresse de même empreinte reprise, sinon créée ; un envoi sans le champ garde l'indication, un champ vide la retire ; sans adresse, pas de `OfferAddress`, l'indication seule n'est pas gardée.
- **Motif** : la demande de l'humain ; un seul endroit dit où est l'offre, sur le modèle de `UserAddress`.
- **Portée** : module Offer (migration, `OfferAddress`, `Offer`, `OfferService`, `OfferForm` app et Filament, `OfferDetailResource`, `RentalDomain`, `Domain`, `OfferBrowseListing`), `DemoSeeder`, app (fiche).
- **Corollaires** : une ancienne version de l'app n'affiche plus l'indication sur la fiche (clé renommée) et un envoi `address_description` est ignoré.
- **Tranché par** : humain
