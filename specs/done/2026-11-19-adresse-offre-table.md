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

---

## Ce que j'ai décidé seul

- `Offer::address` devient une relation « à travers » `OfferAddress` (lecture seule) ; l'écriture passe par `offerAddress`. Les lectures existantes (villes, filtre, titre, fiche) n'ont pas changé.
- La migration lit les lignes avant de modifier les tables : sous SQLite (tests), modifier `offers` reconstruit la table, et la suppression en cascade aurait vidé `offer_addresses`.
- Supprimer une `Address` supprime la ligne `offer_addresses` (cascade), pas l'offre — comme l'ancien `nullOnDelete`.
- Back-office : un groupe Filament lié à `offerAddress`, créé seulement si une adresse est choisie, retiré si on la vide.
- `address_id` reste dans `Domain::COMMON_FIELDS` (aucun attribut ne peut porter ce nom, le formulaire Filament l'utilise).
- Fabrique `Offer::factory()->at($adresse, $indication)` pour les tests.

## Ce qui a été fait

- Web 1bde6c7 : migration `2026_11_29_110000_create_offer_addresses_table` (recopie, retour arrière), modèle `OfferAddress` + fabrique, `Offer::offerAddress()` / `address()`, `OfferService::syncAddress`, champ `indication` (app, back-office, fiche), liste des villes, `DemoSeeder`, traductions, OpenAPI, tests (indication partagée/propre, garder/vider, déplacer, cascade, Filament).
- Mobile 69494f4 : type et fiche lisent `indication`.
- Migration exécutée en local : 21 offres, 21 lignes recopiées.
- Test de migration complété après la QA : recopie de l'indication et retour arrière.
- Suites : serveur 501 verts ; app 233 verts (QA), tsc et lint propres. QA indépendante : GO (`.ia/runs/2026-11-19-adresse-offre-table/qa-check.md`).

## Ce que je n'ai pas vérifié

- Le formulaire Filament dans un vrai navigateur (couvert par les tests Livewire seulement).
- La saisie de l'indication dans l'app au simulateur (couverte par les tests d'API) ; la fiche, elle, a été vue.
- La migration sur une base de production (MySQL local seulement).

## Ce qui reste en suspens

- Une ancienne version de l'app n'affiche plus l'indication sur la fiche et son envoi `address_description` est ignoré (accepté par la décision).
