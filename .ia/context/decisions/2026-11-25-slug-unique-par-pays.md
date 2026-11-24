# Slug `url` unique par pays et par langue

Date : 2026-11-25
Tranché par : l'humain (« sluggable doit vérifier qu'il n'y a pas 2 offres dans le même pays et la même langue avec le même url. faire de même pour partners »)
Complète : 2026-11-25-slug-url-offres-partenaires.md

## Décision

Pour les offres comme pour les partenaires, le slug `url` n'est unique qu'à l'intérieur d'un même pays (`country_id`) et d'une même langue :
- dans le même pays et la même langue, un doublon reçoit un suffixe (`-1`, `-2`…) ;
- dans un autre pays, ou dans une autre langue, le même slug reste libre.

Le contrôle passe par l'option `extraScope` de `spatie/laravel-sluggable`.

## Motif

Une adresse se lit dans un pays et une langue : deux pays peuvent avoir chacun leur « studio-a-bonapriso ».

## Portée

`Offer::getSlugOptions()`, `Partner::getSlugOptions()`.

## Corollaires

- Les offres supprimées (soft delete) comptent encore : le paquet ignore les scopes globaux, donc leur slug n'est pas réutilisé.
- Rien en base ne garantit cette unicité : pas d'index sur la colonne JSON. Seul l'enregistrement par le modèle la vérifie.
- Les slugs déjà générés ne sont pas recalculés.
