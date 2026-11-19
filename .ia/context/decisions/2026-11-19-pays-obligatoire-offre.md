# `2026-11-19` — Le pays d'une offre, obligatoire jusque dans la base

- **Type** : décision
- **Question posée** : le pays d'une offre est obligatoire ; l'imposer aussi dans la base ?
- **Réponse** : « yes enforce the country in the database ».
- **Ce qui est retenu** :
  - `offers.country_id` n'accepte plus de vide ; un pays qui a des offres ne peut plus être supprimé (la base refuse, au lieu de vider le pays des offres) ;
  - au back-office, supprimer un tel pays affiche « Ce pays a encore des offres : il ne peut pas être supprimé. » au lieu d'une erreur ;
  - la migration refuse de s'appliquer s'il reste une offre sans pays ; sous SQLite, les clés étrangères sont coupées pendant la reconstruction de la table (sinon les lignes liées aux offres partiraient en cascade).
- **Motif** : la demande de l'humain ; le formulaire et l'app l'imposaient déjà.
- **Portée** : migration `2026_11_29_200000_require_country_on_offers` (module Offer), `EditCountry` et textes `location::country` (module Location).
- **Corollaires** : pour supprimer un pays, il faut d'abord supprimer ses offres ou les déplacer ; ses adresses et régions partent toujours avec lui.
- **Tranché par** : humain
