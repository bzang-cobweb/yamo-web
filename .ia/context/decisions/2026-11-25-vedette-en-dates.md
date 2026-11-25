# `2026-11-25` — « En vedette jusqu'au » : une date, jour inclus

- **Type** : décision
- **Question posée** : « tous les champs en vedette sont des dates ».
- **Ce qui est retenu** :
  - `featured_until` est une date sans heure, sur les offres (migration qui change la colonne : l'heure enregistrée est perdue, la date gardée) et sur les partenaires (colonne créée en date).
  - En vedette jusqu'à ce jour inclus, comme la date de fin de la période payée (décision 2026-11-24-periode-payee-en-jours).
  - Au back-office, un sélecteur de date, et une date sans heure dans les listes.
- **Remplace** : la date et heure de 2026-11-19-contrat-vedette-favoris et de 2026-11-25-vedettes-d-abord.
- **Tranché par** : humain
