# Type de location : les catégories restent, leurs données migrent

- **Type** : décision
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« keep categories », puis « yes keep both but current categories data go to type de location field »)
- **Complète** : 2026-11-20-contrat-type-de-location.md (en révise le point 3)

## Décision

- **Les catégories d'offre restent** : la table, l'arbre, l'écran du back-office et le champ de l'offre.
- **Le type de location** (`RentalTypeEnum` : `long_term`, `furnished`, `shared`) s'ajoute et remplace le champ « Meublé ».
- **Les trois catégories actuelles** (« Location longue durée », « Location meublée », « Colocation ») deviennent la valeur du type de location de leurs offres. Elles sont ensuite supprimées, et le domaine « location » ne les recrée plus.
