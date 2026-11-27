# `2026-11-27` — Membres d'un partenaire : la photo bordée, en vert s'il est en ligne

- **Type** : décision
- **Question posée** : « mettre une bordure sur les photos membres et mettre la bordure en couleur verte si le membre est en ligne ».
- **Ce qui est retenu** : la photo d'un membre (ou ses initiales), dans la liste des membres et dans son panneau, a une bordure de 2 points : verte (`#2E9D5B`, le vert des pastilles « succès » et de « Administrateur ») s'il est en ligne, discrète (`backgroundSelected` du thème) sinon. « En ligne » vient du serveur : `online` de chaque membre, déjà servi par `ActorData` (le même que sur une carte de notification).
- **Portée** : mobile, `PartnerMembers` (`MemberPhoto`), type `PartnerMember.online`. Aucun changement serveur.
- **Tranché par** : humain
