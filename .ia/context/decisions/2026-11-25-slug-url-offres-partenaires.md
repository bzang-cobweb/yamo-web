# Slug `url` traduisible sur les offres et les partenaires

Date : 2026-11-25
Tranché par : l'humain (« ajouter sluggable sur offre et partnaire, nom du champ est url et translatable » ; choix « Renommer en website » et « spatie/laravel-sluggable »)

## Décision

- Les offres et les partenaires ont un slug traduisible dans la colonne JSON `url`. Il est généré par `spatie/laravel-sluggable` (`HasTranslatableSlug`) : à partir du `title` de l'offre, du `name` du partenaire, dans chaque langue saisie, et unique par langue (suffixe `-1`, `-2`…).
- Le lien vers le site du partenaire, qui s'appelait jusqu'ici `url`, s'appelle désormais `website` : en base, dans le formulaire de l'app, au back-office, dans l'API et dans l'app mobile. Son libellé reste « Lien ».
- Le slug suit le nom ou le titre quand ils changent (comportement par défaut du paquet). Un slug saisi à la main serait gardé.
- L'API expose `url` (le slug), sur la fiche d'une offre et sur un partenaire.
- Les migrations remplissent les slugs des lignes existantes.

## Motif

Préparer des adresses lisibles par langue. Le nom `url` était déjà pris par le lien du site ; l'humain a choisi de le renommer.

## Portée

Modèles `Offer` et `Partner`, migrations `2026_12_06_100000_add_url_slug_to_*`, `PartnerResource`, `OfferDetailResource`, et le type `Partner` de l'app mobile.

## Corollaires

- Nouvelle dépendance : `spatie/laravel-sluggable` ^6.0, autorisée par l'humain le 2026-11-25.
- La liaison des routes reste par identifiant : les URL auto-réparatrices du paquet ne sont pas activées.
- Le slug n'est pas modifiable au back-office.
- Un renommage change le slug : une adresse partagée avec l'ancien slug ne mène plus nulle part tant qu'aucune route ne le résout.
