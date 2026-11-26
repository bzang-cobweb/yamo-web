# Contrat — Écrire à l'éditeur d'une offre

Validation : non — exécuté en mode nuit le 2026-11-26

## Demande

« Depuis la fiche offre, ajouter un bouton envoyer un message dans le contenu de la section éditeur pour envoyer un
message à l'éditeur au sujet de cette offre. Cela ouvrirait donc un canal de discussion entre les 2 où ils peuvent
s'écrire. Quelle solution ? »

## Ce que j'ai compris

Un membre qui regarde l'offre d'un autre veut lui écrire à propos de cette offre, et l'échange doit continuer dans les
deux sens : une discussion par offre et par membre (le membre ↔ l'éditeur).

## Solution retenue : la discussion est un fil de notifications

L'app a déjà tout ce qu'il faut pour une discussion (décisions 2026-11-20-suivi-demande, 2026-11-20-suivi-conversation,
2026-11-20-reponse-dans-son-fil) :

- une notification par action, enregistrée chez qui la reçoit, visible aussi de qui l'a faite (`user_id`) ;
- les notifications d'un même fil (`thread`) ne font qu'une carte dans l'onglet Notifications, la plus récente, avec
  les non-lus ;
- la page d'une notification montre tout le fil en bulles : « Toi » à droite, l'autre à gauche avec sa photo et son
  nom, le message, la date ; un bouton en bas (`link` / `link_label`).

Un message est donc **une notification** envoyée à l'autre partie, dans le fil `offer-message:{offre}:{membre}`. Pas de
nouvelle table, pas de nouvel écran de conversation, pas de dépendance. Les canaux du membre (e-mail, SMS) suivent ses
préférences, comme les autres notifications.

Écartées : un module de messagerie dédié (tables conversations/messages, liste, écran de fil : plusieurs jours, et un
second endroit où lire ses échanges) ; WhatsApp / téléphone (expose le numéro).

## Découpage

### Serveur (web)

1. **Formulaire `offer.message`** (`OfferMessageForm`), servi par l'API comme les autres :
   - `?record={offre}` : un membre écrit à l'éditeur ; `?record={offre}.{membre}` : l'éditeur répond à ce membre ;
   - un champ `message` (zone de texte, obligatoire, 1000 caractères au plus) ;
   - titre « Envoyer un message », sous-titre le titre de l'offre (et le prénom du membre pour une réponse) ;
   - envoi `POST /api/v1/offers/{offer}/messages` (`message`, et `member` pour une réponse) ; succès : retour, « Message
     envoyé. ».
2. **Droits** (policy `OfferPolicy::message`) : connecté ; offre visible des autres ; un membre n'écrit pas à sa propre
   offre ; l'éditeur ne répond qu'à un membre qui lui a déjà écrit sur cette offre.
3. **Notification `OfferMessageReceived`** (database + canaux du destinataire), fil `offer-message:{offre}:{membre}`,
   `user_id` = l'expéditeur : titre « Message », le texte du message, l'offre liée (le titre mène à la fiche), bouton
   « Répondre » vers le formulaire (`record` qui convient au destinataire).
4. **Fiche** (`OfferDetailResource`) : la section « Éditeur » porte une action `{label: "Envoyer un message", link}`
   vers le formulaire, sauf pour l'éditeur lui-même ; servie aussi sans connexion (le formulaire demande la connexion,
   comme « Réserver »).
5. Limite : 20 messages par minute et par compte.

### App (mobile)

6. Une section de la fiche peut porter une `action` (`label`, `link`) : un bouton bordé sous ses lignes, qui ouvre le
   lien comme les autres liens de l'app.
7. Rien d'autre : la carte et la page de la notification, la réponse par le formulaire et le retour au fil existent déjà.

## Critères de succès

- Sur la fiche d'une offre d'un autre, la section « Éditeur » montre « Envoyer un message » ; sur sa propre offre, non.
- Le membre envoie un message : l'éditeur a une notification « Message » avec le texte ; le membre voit le fil dans ses
  notifications, son message à droite (« Toi »).
- L'éditeur touche « Répondre », écrit : le membre reçoit la réponse dans le même fil (une seule carte), les deux
  messages dans l'ordre sur la page.
- Un membre ne peut pas s'écrire à lui-même ; l'éditeur ne peut pas écrire le premier à un membre ; un inconnu ne peut
  pas écrire dans le fil d'un autre (403/404).
- Tests web et mobile verts ; Pint, tsc.

## Ce que j'ai décidé seul

(à compléter à la clôture)

## Ce qui a été fait

## Ce que je n'ai pas vérifié

## Ce qui reste en suspens
