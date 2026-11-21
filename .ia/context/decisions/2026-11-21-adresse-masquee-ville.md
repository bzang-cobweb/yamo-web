# Adresse masquée : la ville seule

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-21-afficher-adresse.md

## Décision
Quand l'adresse est masquée, seule la ville s'affiche, et jamais l'indication :
- **Sur la fiche de l'offre** (`show_address` faux) : le bloc « Localisation » montre la ville
  seule (`address` vaut la ville).
- **Sur le détail d'une demande** (`show_address` faux) : pour le demandeur, la ligne « Adresse »
  montre la ville seule ; sans ville, la ligne est retirée.
- **Le propriétaire** voit toujours l'adresse entière.

Cette décision remplace la règle précédente de 2026-11-21-afficher-adresse, qui ne montrait rien :
pas de bloc « Localisation » sur la fiche, pas de ligne « Adresse » sur la demande.

## Motif
Demande de l'humain du 2026-11-21 : « si show_address est faux alors on affiche juste la ville ».
