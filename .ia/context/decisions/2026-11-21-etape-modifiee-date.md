# « Modifié » près de la date d'une étape modifiée

- **Date** : 2026-11-21
- **Type** : décision
- **Demande** : « mettre Edited sur l'affichage de la date pour une notification de type modifiée »

## Décision
Dans le suivi d'une demande (page d'une notification), une étape « Demande modifiée » affiche
« Modifié » avant sa date (« Modifié 10:47 »), comme une messagerie. Texte servi par l'API :
`notification::mobile.notifications.edited` (fr « Modifié », en « Edited », de « Bearbeitet »).

## Mécanisme (corrigé à la demande de l'humain : pas de drapeau dans les données)
Une étape est modifiée quand son `updated_at` diffère de son `created_at` ; l'API le sert par
étape (`history[].edited`), l'app l'affiche.
- L'étape « Demande modifiée » du propriétaire reprend le `created_at` de celle qu'elle
  remplace (contrat `HasNotificationCreatedAt`, lu par `DatabaseChannel`) ; `updated_at` est
  l'heure de la modification. La copie du membre, réécrite sur place, garde son `created_at`.
- Marquer lu ne touche plus `updated_at` (requêtes sans horodatage) ; une migration remet
  `updated_at` à `created_at` pour les notifications existantes.
- Corollaire : une demande modifiée ne remonte plus en tête de liste (son `created_at` reste
  celui de la demande).

## Portée
Le suivi seulement ; les cartes de la liste ne changent pas.
