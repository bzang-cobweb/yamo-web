# Pas de date de disponibilité pour un meublé

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-disponibilite-demande.md

## Décision

Un logement meublé n'a pas de date de disponibilité (« Disponible dès le ») : il se réserve par
dates. Partout :

- formulaire (app et back-office) : champ masqué, valeur non enregistrée ;
- fiche : pas de ligne ;
- lignes d'une demande : seulement pour une visite ;
- filtre « Disponible dès le » de la vitrine : un meublé y passe toujours ;
- démo : aucun meublé n'a de date ; les 7 meublés locaux ont été vidés.

## Motif

Demande de l'humain du 2026-11-20 : « enlever partout disponibilité pour le meublé ».
Comportement du filtre pour un meublé : décidé seul.

## Corollaires

Deux défauts corrigés en passant, de la règle qui exclut un champ masqué (`exclude_unless`) :

- elle ne retenait que la première valeur admise : les charges, la caution et l'avance d'une
  colocation (`shared`) étaient ignorées à l'enregistrement depuis l'app ;
- dans le back-office, elle nommait le champ voisin sans son préfixe (`rental_type` au lieu
  de `details.rental_type`) et excluait toujours la valeur : retirée là, où la condition
  d'affichage de Filament suffit.
