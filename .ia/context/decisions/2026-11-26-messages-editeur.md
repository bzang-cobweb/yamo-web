# `2026-11-26` — Écrire à l'éditeur d'une offre : la discussion est un fil de notifications

- **Type** : décision
- **Question posée** : « Depuis la fiche offre, ajouter un bouton envoyer un message dans le contenu de la section éditeur pour envoyer un message à l'éditeur au sujet de cette offre. Cela ouvrirait donc un canal de discussion entre les 2 où ils peuvent s'écrire. Quelle solution ? » (contrat `specs/2026-11-26-messagerie-offre.md`).
- **Ce qui est retenu** :
  - un message est **une notification** `OfferMessageReceived`, envoyée à l'autre partie (onglet Notifications, puis e-mail / SMS selon ses préférences), dans le fil `offer-message:{offre}:{membre}`, où le membre est toujours celui qui n'est pas l'éditeur ; `user_id` = l'expéditeur, qui retrouve son message dans le fil ; pas de table ni d'écran de conversation ;
  - données : `title` « Message », `body` et `message` = le texte (la bulle du fil montre `message`), `summary` « Au sujet de « titre » » (la carte et l'en-tête de la page ; le titre mène à la fiche par `text_links`), `offer_id`, `member_id`, `link` vers le formulaire `offer.message` avec le `record` du lecteur (`{offre}.{membre}` pour l'éditeur, `{offre}` pour le membre), `link_label` « Répondre » ; l'étape du fil s'intitule « Message » ;
  - son propre message, lu par son expéditeur, est rédigé pour lui (`OfferNotificationFilter::forReader`) : sa langue et son lien de réponse ;
  - formulaire `offer.message` : un champ `message` (zone de texte, obligatoire, 1000 caractères) et un champ caché `member` (le membre à qui répond l'éditeur, vide sinon) ; envoi `POST /api/v1/offers/{offer}/messages`, 20 par minute, 204 ;
  - droits (`OfferPolicy::message`) : offre visible des autres (sinon 404) ; un non-éditeur écrit à l'éditeur ; l'éditeur ne répond qu'à un membre qui lui a déjà écrit au sujet de cette offre ; personne ne s'écrit à soi-même ni dans la discussion d'un autre (403) ; pas d'exception pour le super administrateur ;
  - la fiche : la section `publisher` porte toujours une clé `action` : `{label: "Envoyer un message", link: "/profile/offer.message?record={offre}"}`, ou `null` pour l'éditeur ; servie aussi sans connexion (le formulaire demande la connexion, comme « Réserver »).
- **Conséquence** :
  - la route n'a pas de garde `can:` : la policy dépend du membre envoyé dans le corps ; `OfferMessageRequest::authorize()` l'appelle (exception notée dans `RouteGuardTest`) ;
  - le message est écrit dans l'onglet avant la réponse de l'API (connexion `sync` pour `database`, comme les demandes) : l'expéditeur le voit dès son retour ;
  - le filtre « Type » des notifications ne propose pas encore « Message » ; le filtre « Membre » et la recherche par titre d'offre s'appliquent déjà.
- **Tranché par** : session de nuit (champ caché `member` plutôt qu'un paramètre de requête ; `action` toujours présente, `null` pour l'éditeur ; `summary` pour nommer l'offre ; e-mail avec le texte, SMS court sans le texte ; pas de type « Message » dans le filtre)
