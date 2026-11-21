# Démo : une location et une colocation pour l'autre membre

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-21-demo-logement-autre-membre.md

## Décision
Les données de démonstration (`DemoSeeder::otherOffer`, liste `OTHER_OFFERS`) donnent au premier
visiteur, propriétaire de « Studio meublé à Bonanjo », deux autres logements publiés, à la même
adresse, avec deux photos chacun :
- « Appartement deux chambres à Bonanjo » : location (`long_term`), 180 000 FCFA par mois,
  charges 15 000, caution 2 mois, avance 6 mois, libre tout de suite ;
- « Chambre en colocation à Bonanjo » : colocation (`shared`), 60 000 FCFA par mois, charges
  10 000, caution 1 mois, avance 3 mois, libre tout de suite.
Rejouable : chaque logement n'est créé qu'une fois. Appliqué à la base locale : DemoSeeder
relancé sans réinitialisation, il n'ajoute que ce qui manque.

## Motif
Demande de l'humain du 2026-11-21 : « ajouter un logement de type location et colocation de test
à l'autre utilisateur qui a déjà studio meublé à bonanjo ». Titres, prix et détails : décidés seul.
