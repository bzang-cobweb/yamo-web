# `2026-11-19` — Pas de retrait de son avis sur une offre archivée

- **Type** : décision
- **Question posée** : un membre peut-il retirer son avis sur une offre archivée (aujourd'hui : 404) ?
- **Réponse** : « No, keep it as is ».
- **Ce qui est retenu** : on garde le comportement actuel. Gérer son avis (le donner, le modifier ou le retirer) n'est possible que sur une offre publiée du pays courant ; sur une offre archivée, la demande répond 404.
- **Motif** : les avis d'une offre non publiée ne s'affichent nulle part, donc rien n'est exposé tant que l'offre reste archivée.
- **Portée** : règle `review` de `OfferPolicy` ; routes `PUT|DELETE /api/v1/offers/{offer}/review`.
- **Corollaires** : si l'offre est republiée, ses avis réapparaissent, et leurs auteurs peuvent de nouveau les modifier ou les retirer.
- **Tranché par** : humain
