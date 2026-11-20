# La caution d'un meublé : un montant

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-caution-avance-loyer.md

## Décision

Un logement meublé a un champ `deposit`, « Caution » : un montant dans la devise du pays,
facultatif, entier, dans l'étape « Prix et disponibilité » après l'avance de loyer. Caché et
non enregistré pour une location non meublée, qui a sa caution en mois (`deposit_months`).

## Motif

Demande de l'humain du 2026-11-20 : « les 2 précédents champs ne concernent pas le type de
location meublé. ajouter un champ pour le montant de la caution pour le type location
meublé ». Facultatif et sans unité de temps : décidé seul.

## Corollaires

- La démo donne 50 000 ou 100 000 de caution aux meublés qu'elle crée ; les logements déjà en
  base locale n'en ont pas.
- La caution n'apparaît pas dans les lignes d'une demande de séjour : à décider à part.
