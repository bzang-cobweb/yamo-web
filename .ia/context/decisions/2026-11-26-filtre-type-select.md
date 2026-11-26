# `2026-11-26` — Filtre « Type » des notifications : de nouveau une liste

Remplace : 2026-11-26-filtre-type-en-puces.md

- **Type** : décision
- **Question posée** : « remettre le champ select sur le filtre type ».
- **Ce qui est retenu** :
  - le filtre `type` des notifications redevient un champ `select` : un seul type ;
  - le reste de 2026-11-26-filtre-type-en-puces tient : le type « Message » (`offer_message`), les types présents seulement, « Statut » quand le type choisi a des statuts, « Logement » et « Type de location » pour « Réservation » ;
  - le serveur lit encore une liste (`filter[type][]=…`) ; l'app sait toujours lire un parent en puces (`dependentOptions`).
- **Motif** : choix de l'humain.
- **Portée** : la liste des notifications.
- **Tranché par** : humain
