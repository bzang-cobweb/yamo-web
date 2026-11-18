# `2026-11-18` — Commande `project:init` et skill `projet-domaine`

- **Type** : décision
- **Question posée** :
  - valider le contrat `specs/2026-11-18-initialisation-par-domaine.md` ;
  - la commande écrit-elle elle-même dans `.env` ?
- **Réponse** : « Approved », recommandation retenue.
- **Ce qui est retenu** :
  - `project:init` vérifie tout avant d'écrire ;
  - elle écrit elle-même dans `.env` ses seules clés non secrètes : `APP_NAME`, `APP_LOCALE`, `OFFER_DOMAIN`, `AUTH_CHANNEL_EMAIL`, `AUTH_CHANNEL_PHONE` ;
  - elle active les modules, migre, alimente la base, active les pays et affiche un résumé ;
  - on peut la relancer, et elle exige `--force` en production ;
  - le skill guide, lance et vérifie. Il ne touche jamais `.env` lui-même, et n'invente pas de domaine.
- **Motif** : un projet opérationnel en quelques réglages, avec toute la logique dans du code testé.
- **Portée** : application (commande), `.ia/skills/projet-domaine`.
- **Corollaires** : aucun secret n'est lu ni écrit par la commande.
- **Tranché par** : humain
