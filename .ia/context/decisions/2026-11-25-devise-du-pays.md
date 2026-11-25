# `2026-11-25` — La devise d'une offre est celle de son pays

- **Type** : décision
- **Question posée** : « à quoi sert le champ currency sur offers, une offre est déjà liée à un pays qui a la currency », puis « supprimer currency et utiliser celui du pays de l'offre ».
- **Ce qui est retenu** :
  - **La colonne `offers.currency` est retirée** (migration ; le retour arrière la recrée et la remplit avec la devise du pays).
  - **`$offer->currency`** est un accesseur qui renvoie la devise du pays de l'offre. Toutes les lectures restent les mêmes ; les écritures (API, back-office, données de démonstration) sont retirées.
  - **Les listes** chargent le pays avec les offres, pour ne pas multiplier les requêtes.
  - **Conséquence assumée** : changer la devise d'un pays au back-office change la devise affichée de tous les prix de ses offres. Les prix ne sont pas convertis.
  - **Les réservations** gardent leur propre `currency` : elle fige le total d'une demande.
- **Tranché par** : humain
