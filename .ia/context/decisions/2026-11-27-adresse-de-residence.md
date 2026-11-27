# `2026-11-27` — Adresse de résidence : une adresse du compte, marquée, une seule

- **Type** : décision
- **Question posée** : « Menu profile ajouter un lien residential address ».
- **Ce qui est retenu** :
  - lien « Adresse de résidence » dans « Informations personnelles », après le mot de passe, icône maison
    (`house` / `home`) ;
  - formulaire `auth.profile.residence` (`PUT /api/v1/auth/me/residence`) : les champs d'une adresse du compte
    (rue avec suggestions, NPA, région, ville, indications), repris d'`UserAddressForm::addressFields()`, sans le
    destinataire ; prérempli par l'adresse enregistrée ;
  - stockage : une `UserAddress` marquée `is_residence` (colonne booléenne, faux par défaut), une seule par compte,
    créée ou modifiée par `UserAddressService::saveResidence()` ; le destinataire est le compte (prénom, nom) ;
  - elle n'apparaît pas dans « Mes adresses » (`UserAddressListing`) et le formulaire `auth.address` ne l'ouvre pas.
- **Décidé seul (mode nuit)** :
  - la région se choisit parmi celles du pays courant, comme pour « Mes adresses » (pas de choix du pays) ;
  - l'adresse partagée (`Address`) suit la règle des adresses du compte : reprise si identique, jamais modifiée ;
  - une note sous le titre dit qu'elle n'est pas montrée aux autres membres ; au back-office, elle apparaît parmi
    les adresses de l'utilisateur, sans marque distincte (pas encore).
- **Portée** : module Auth (`ProfileMenuKey`, `ProfileMenuService`, `ResidenceForm`, `UserAddressForm`,
  `UserAddressService`, `UserAddressListing`, `User::residence()`), aucune version de l'app.
- **Tranché par** : humain (demande), session de nuit pour le détail
