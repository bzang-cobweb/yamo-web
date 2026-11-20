# Les charges et le total d'une visite

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-lignes-demande.md

## Décision

Dans les lignes d'une demande de visite (logement non meublé), après « Prix par mois » :

- « Charges par mois » : les charges du logement (`details.charges`), toujours affichées,
  0 si elles ne sont pas renseignées ;
- « Total par mois » : prix + charges, toujours affiché, même égal au prix.

Un séjour (meublé, sans charges) garde son total figé de la demande.

## Motif

Demande de l'humain du 2026-11-20 : « afficher la ligne charges par mois sur détail
notification après prix ; toujours mettre cette ligne pour les visites même si elle est à 0 ;
calculer le total en additionnant ces 2 lignes et donc on affiche toujours le total même s'il
est égal au prix ». Libellé « Total par mois » (et non « Total ») : décidé seul.

## Corollaires

- Le prix et les charges sont ceux du logement au moment de l'affichage, pas figés à la
  demande (une visite n'a pas de total enregistré).
