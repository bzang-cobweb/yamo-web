# Demande modifiée : une étape « Demande modifiée » remplace la précédente

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : le message modifié d'une demande en attente n'apparaissait pas dans la notification ; choix de l'humain : « Option 1, supprimer ».

## Décision
Quand le membre modifie sa demande en attente, l'étape précédente du fil (« Demande envoyée »
ou une « Demande modifiée » antérieure) est remplacée par une étape `updated`, « Demande
modifiée », qui porte le nouveau message, les dates et le total :
- chez le propriétaire, l'ancienne notification est supprimée et la nouvelle lui est envoyée
  comme une demande : non lue, sur tous ses canaux (push, e-mail, SMS), carte remontée ;
- chez le membre, sa copie est réécrite sur place (même identifiant), remontée, déjà lue.

## Motif
Le propriétaire doit savoir que la demande a changé (dates comprises), et la conversation ne
garde qu'une étape pour la demande, avec son dernier message. La copie du membre garde son
identifiant : la page qu'il a ouverte pour modifier reste valide au retour du formulaire.

## Écartée
Réécrire « Demande envoyée » sur place et la remettre en non lue : le propriétaire n'était
prévenu que dans l'app, sans push ni e-mail.

## Corollaires
`BookingStepEnum::UPDATED`, événement `BookingUpdated`, textes `offer::booking.notify.updated`
et `history.updated` (fr, en, de).
