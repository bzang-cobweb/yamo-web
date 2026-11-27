# `2026-11-27` — Plus de genre dans les formulaires

- **Type** : décision
- **Question posée** : « Enlever le genre dans tous les formulaires. »
- **Ce qui est retenu** : le champ genre disparaît de l'inscription, des informations personnelles, de l'adresse (le
  destinataire) et des formulaires du back-office (fiche utilisateur, adresses). Les colonnes `users.gender` et
  `user_addresses.gender` restent, avec leurs valeurs : plus rien ne les écrit, une adresse modifiée garde la sienne.
  L'API sert encore `gender` dans `/me`.
- **Décidé seul (mode nuit)** : garder les colonnes (pas de suppression de données la nuit) ; « tous les formulaires »
  comprend ceux du back-office.
- **Portée** : module Auth.
- **Tranché par** : humain (demande), session de nuit pour la portée
