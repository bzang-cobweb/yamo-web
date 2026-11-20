# Page d'une notification : l'en-tête de la première étape de la demande

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-suivi-demande.md

## Décision

Sur la page d'une notification qui appartient à une demande, l'en-tête montre
toujours la première étape de la demande (la demande envoyée), quelle que soit
l'étape ouverte : son titre, son statut (le statut réel de la demande), qui l'a faite,
son texte sans le nom (`summary`) et son prix (`total`). Le message, le bouton et le
lien restent ceux de la notification ouverte ; le suivi dessous ne change pas.

Côté serveur seulement : `NotificationResource`, avec le suivi, prend ces champs dans
la première étape de `NotificationThreads::steps()`. L'app n'a rien à changer.

## Motif

Demande de l'humain du 2026-11-20 : « sur le détail de la notification toujours
afficher le titre, le statut, le summary et le prix de la première notification ».

## Portée

La page d'une notification (`GET /notifications/{id}`). Pas les cartes de la liste.

## Corollaires

- La première étape est la notification reçue par le propriétaire : son texte est
  dans la langue du propriétaire, et le membre qui ouvre sa propre notification y lit
  « <son nom> demande un séjour … ».
