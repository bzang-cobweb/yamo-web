# `2026-11-19` — « Mes logements » : la date d'expiration sur chaque item

- **Type** : décision
- **Question posée** : demande directe de l'humain : « afficher la date d'expiration dans mes logements ».
- **Réponse** : la demande elle-même ; la forme est choisie par la session.
- **Ce qui est retenu** :
  - dans la ligne en gras de l'item, après le statut : « Publiée · Expire le 30 juin 2027 · … », ou « Expirée le 2 janvier 2026 » une fois la date passée ;
  - sans date d'expiration, rien n'est ajouté ;
  - la date est écrite dans la langue du compte ; servie par Laravel (`OfferListing`), l'app n'est pas modifiée.
- **Motif** : demande de l'humain ; l'auteur voit quand son offre cessera d'être visible si le paiement est appliqué (décision 2026-11-19-contrat-paiement-visibilite-avis).
- **Portée** : `OfferListing`, textes `offer::offer.list.expires` / `expired`.
- **Corollaires** : la date s'affiche que le paiement soit appliqué ou non.
- **Tranché par** : humain (forme par la session)
