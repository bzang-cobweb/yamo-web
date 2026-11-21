# « Modifié » près de la date d'une étape modifiée

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : « mettre Edited sur l'affichage de la date pour une notification de type modifiée »

## Décision
Dans le suivi d'une demande (page d'une notification), une étape « Demande modifiée » affiche
« Modifié » avant sa date (« Modifié 10:47 »), comme une messagerie. Texte servi par l'API :
`notification::mobile.notifications.edited` (fr « Modifié », en « Edited », de « Bearbeitet »).

## Mécanisme
Générique : la notification porte `data.edited` (vrai pour l'étape `updated` de
`BookingRequested`) ; l'API le sert par étape (`history[].edited`), l'app l'affiche.

## Portée
Le suivi seulement ; les cartes de la liste ne changent pas.
