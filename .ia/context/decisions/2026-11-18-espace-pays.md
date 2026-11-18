Remplacée en partie par : 2026-11-18-ecran-entree-toujours.md (affichage de l'écran d'entrée).


# `2026-11-18` — Espace pays : choix du pays à l'entrée, devise par pays

- **Type** : décision
- **Question posée** :
  - un compte par pays ou un seul ?
  - un écran de choix avec un seul pays ?
  - quelle apparence par pays ?
  - la devise par pays dans ce chantier ?
  - puis valider le contrat `specs/2026-11-18-espace-pays.md`.
- **Réponse** :
  - un seul compte pour tous les pays ;
  - pas d'écran de choix tant qu'un seul pays est ouvert ;
  - « Flag only » ;
  - devise par pays incluse ;
  - « Approved ».
- **Ce qui est retenu** :
  - **en entrant,** on choisit son pays, sauf s'il n'y en a qu'un ouvert. Le choix est gardé sur l'appareil ;
  - **le drapeau seul** rappelle le pays dans la barre du haut ; quand plusieurs pays sont ouverts, il permet d'en changer ;
  - **à chaque requête,** l'app envoie `X-Country`. Le serveur le résout ; avec un seul pays actif, ce pays s'impose ;
  - **offres et adresses du compte appartiennent au pays courant.** Hors de ce pays, elles répondent 404 ;
  - **la devise est celle du pays** (`countries.currency`) et non plus celle du domaine.
- **Motif** : la notion de pays est forte. Changer de pays doit donner l'impression de changer d'app, tout en gardant une seule app et un seul compte.
- **Portée** : modules Location, Offer et Auth (adresses, téléphone) ; app mobile (entrée, en-tête, barre du haut).
- **Corollaires** :
  - aucun thème propre à un pays ;
  - le back-office voit tous les pays ;
  - `Domain::currency()` disparaît.
- **Tranché par** : humain
