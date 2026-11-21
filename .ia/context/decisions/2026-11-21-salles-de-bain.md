# Logement : le nombre de salles de bain, et son filtre

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active

## Décision

### Champ
- Attribut `bathrooms` du domaine location : un nombre facultatif, de 0 à 20, libellé « Salles de
  bain ».
- Il se saisit dans l'étape « Caractéristiques », juste après les chambres.
- Comme tous les attributs du domaine, il apparaît aussi dans le back-office et dans les
  caractéristiques de la fiche.

### Filtre
- « Salles de bain » : au moins 1+, 2+ ou 3+.
- Il se place juste après « Chambres », partout où ce filtre existe : l'accueil et « Mes
  logements » (`RentalDomain::filters`).
- Un logement sans nombre de salles de bain renseigné est écarté dès que ce filtre est choisi.

## Motif
Demande de l'humain du 2026-11-21 : « ajouter le champ nombre de salles de bain et rajouter comme
filtre partout où il y a le filtre du nombre de chambres dans l'offre ».

## Ce que j'ai décidé seul
- Le champ est facultatif, alors que les chambres sont obligatoires.
- Le filtre va jusqu'à 3+.
