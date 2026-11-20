# Charges, caution et avance masquées quand elles sont vides

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Remplace** : 2026-11-20-caution-dans-le-total.md (sur le point « toujours, même à 0 »)

## Décision

Dans les lignes d'une demande, « Charges mensuelles », la caution et l'avance n'apparaissent que
si le logement les renseigne avec une valeur non nulle. Le total, lui, reste toujours affiché :

- visite : loyer × (mois de caution + mois d'avance), 0 sans l'un ni l'autre ;
- séjour : total de la demande + caution.

## Motif

Demande de l'humain du 2026-11-20 : « ne pas afficher ces champs s'ils sont vides : charges
mensuelles, caution, avance ». Une valeur 0 compte comme vide : décidé seul.

## Corollaires

- La démo (`DemoSeeder::WITHOUT_FEES`) en donne un exemple : la « Maison familiale à Bonamoussadi »
  (visite refusée) et le « Studio meublé pour courts séjours » (séjour annulé) n'ont ni
  charges, ni caution, ni avance.
