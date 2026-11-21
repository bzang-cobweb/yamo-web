# « Modifié » près de la date d'une étape modifiée

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : « mettre Edited sur l'affichage de la date pour une notification de type modifiée »

## Décision
Dans le suivi d'une demande (page d'une notification), une étape « Demande modifiée » affiche
« Modifié » avant sa date (« Modifié 10:47 »), comme une messagerie. Texte servi par l'API :
`notification::mobile.notifications.edited` (fr « Modifié », en « Edited », de « Bearbeitet »).

## Mécanisme
Générique : la notification porte `data.edited` ; l'API le sert par étape (`history[].edited`),
l'app l'affiche. Pour `BookingRequested`, `edited` est vrai à une modification seulement si le
message diffère de celui de l'étape remplacée ; une étape déjà modifiée le reste. Changer
seulement les dates ou les voyageurs ne la marque pas.

## Écartée
« Modifié » d'après un `updated_at` différent du `created_at` (essayé puis annulé à la demande
de l'humain) : les lectures touchaient `updated_at`, et la demande ne remontait plus en tête.

## Portée
Le suivi seulement ; les cartes de la liste ne changent pas.
