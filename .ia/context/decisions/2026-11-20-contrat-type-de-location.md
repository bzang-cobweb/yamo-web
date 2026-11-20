# Contrat du chantier « Type de location », validé

- **Type** : décision
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« Approved »)
- **Remplace** : 2026-11-18-offres-multilingues-categories-arbre.md (pour les catégories)

## Décision

Le contrat `specs/2026-11-20-type-de-location.md` est validé tel qu'écrit, avec ses trois points tranchés par défaut :

1. Le champ « Meublé » disparaît : le type de location décide (séjour par jour pour une location meublée ; visite par mois sinon).
2. Une colocation se visite, avec un prix et des charges par mois.
3. Les catégories disparaissent entièrement, y compris leur écran au back-office.

## Corollaires

- Le type de location est une liste fixe dans le code (`RentalTypeEnum`) : `long_term`, `furnished`, `shared`.
