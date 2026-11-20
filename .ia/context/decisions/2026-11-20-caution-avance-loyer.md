# La caution et l'avance de loyer d'un logement, en mois

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active

## Décision

Un logement a deux champs de plus, dans l'étape « Prix et disponibilité », après les charges :

- `deposit_months` — « Caution », en mois ;
- `advance_months` — « Avance de loyer », en mois.

Entiers de 0 à 24, facultatifs, unité « mois ». Comme les charges, ils ne valent que pour une
location non meublée (longue durée, colocation) : cachés et non enregistrés pour un meublé.

## Motif

Demande de l'humain du 2026-11-20 : « ajouter une colonne pour le nombre de mois de caution et
pour le nombre de mois d'avance de loyer ». Décidé seul :

- rangés dans `details`, comme les autres caractéristiques du domaine `rental`, et non en
  colonnes de la table `offers` (pas de migration) ;
- réservés au non meublé, facultatifs, plafond de 24 mois.

## Corollaires

- La démo (`DemoSeeder`) donne 2 mois de caution et 3, 6 ou 12 mois d'avance aux logements non
  meublés qu'elle crée ; les logements déjà en base locale n'en ont pas.
- Ni la caution ni l'avance ne figurent dans les lignes d'une demande de visite : à décider à part.
