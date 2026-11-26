# `2026-11-27` — « Envoyer un message » ouvre la conversation déjà commencée

Complète : 2026-11-26-messages-editeur.md, 2026-11-26-message-editeur-en-panneau.md

- **Type** : décision
- **Question posée** : « page detail offre l'utilisateur veut envoyer le message, s'il a déjà envoyé le message à ce propriétaire au sujet de cette offre (offer message) ouvrir la page de detail de la notification ».
- **Ce qui est retenu** : sur la fiche, le bouton « Envoyer un message » de la section Éditeur mène, pour un membre qui a déjà écrit à l'éditeur au sujet de cette offre, à la page de la dernière notification de leur fil (`offer_message:{offre}:{membre}`), où il voit la conversation ; sinon, au formulaire de message (dans un panneau). Le serveur choisit le lien (`OfferDetailResource::publisherAction`), l'app ouvre ce qu'on lui donne.
- **Tranché par** : humain
