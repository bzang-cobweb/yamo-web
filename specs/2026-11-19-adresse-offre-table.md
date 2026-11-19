# Contrat — Adresse d'une offre : table `offer_addresses` (adresse + indication)

Validation : en attente

Demande : « enlever le champ address_description et faire une table OfferAddress qui référence une Address et contient en plus un champ indication ».

## Ce que j'ai compris

L'adresse d'une offre suit le modèle de l'adresse d'un compte (`UserAddress` :
l'adresse partagée + ce qui est propre au compte). Une offre a **au plus une**
`OfferAddress`, qui pointe vers l'`Address` partagée du pays et porte
l'`indication` propre à l'offre (« derrière le marché, portail bleu »).

- Nouvelle table `offer_addresses` : `offer_id` (unique, supprimé avec l'offre),
  `address_id` (supprimé avec l'adresse), `indication` (texte de 500 caractères au
  plus, facultatif, **non traduit**, comme le choix du 2026-11-19), horodatage.
- `offers.address_description` **et** `offers.address_id` disparaissent : le lien
  vers l'adresse passe dans `offer_addresses`, pour qu'il n'y ait qu'un seul endroit
  qui dise où est l'offre. Une migration recopie les données existantes avant de
  retirer les colonnes.
- `Offer::address` reste lisible tel quel (relation « à travers »
  `OfferAddress`) : villes, filtre par ville, titre de liste, fiche, pays de l'offre
  ne changent pas de comportement.
- Le champ du formulaire s'appelle désormais `indication` (app et back-office) ; la
  fiche le sert sous la clé `indication`. Même place qu'aujourd'hui : après la ville
  à l'étape « Adresse », sous l'adresse dans l'onglet « Annonce », bloc « Adresse »
  de la fiche.
- Mêmes règles d'enregistrement qu'aujourd'hui : une adresse de même empreinte est
  reprise, sinon créée ; changer d'adresse déplace la `OfferAddress` (l'`Address`
  partagée n'est jamais modifiée) ; un envoi sans le champ garde l'indication, un
  champ vide la retire.
- Sans adresse (ni rue ni ville), pas de `OfferAddress` : l'indication seule n'est
  pas gardée.

## Découpage

- Web : migration (création, recopie, retrait des colonnes, retour arrière),
  modèle `OfferAddress` + factory, relations sur `Offer`, `OfferService`,
  `OfferForm` (app), formulaire Filament (groupe lié à `offerAddress`),
  `OfferDetailResource`, `RentalDomain` (étape), `Domain::COMMON_FIELDS`,
  `OfferBrowseListing::cities()`, `DemoSeeder`, traductions, tests, OpenAPI.
- App : type `OfferDetail` et fiche (`indication` au lieu de `address_description`), test.

## Critères de succès (état observable)

- La base n'a plus `offers.address_id` ni `offers.address_description` ; les offres
  existantes gardent leur adresse et leur indication après migration.
- Deux offres à la même adresse partagent la même `Address` et ont chacune leur
  indication.
- Créer, modifier, vider l'indication depuis l'app et depuis le back-office fonctionne ;
  la fiche l'affiche sous « Adresse ».
- Filtre par ville, liste des villes, titre de liste et fiche inchangés pour l'utilisateur.
- Suites serveur et app vertes, tsc et lint propres, OpenAPI régénéré, QA indépendante.

## Points à trancher (défaut appliqué si validé tel quel)

1. Retirer aussi `offers.address_id` (défaut : **oui**, le lien vit dans
   `offer_addresses`). Alternative : le garder et dupliquer le lien.
2. Nom de la clé servie et du champ : `indication` (défaut).
